<?php
// tests/Integration/FeedTest.php
// RF-4 y RNF-3: el feed de la vitrina entrega motos de 20 en 20 por paginado
// de clave (id menor al último de la tanda), ordenado por creación con
// desempate por id, sin repetir ni perder motos aunque se publiquen nuevas en
// el medio; editar una moto no cambia su posición.

declare(strict_types=1);

/**
 * Dueño de prueba para las motos del feed.
 */
function feed_owner_id(): int
{
    return (int) register_user(test_db(), 'Feed Owner', 'feed@x.com', 'moto2026', '1144444444')['user_id'];
}

/**
 * Crea $count motos directo en la base con fechas crecientes: las primeras
 * quedan como las más viejas, igual que en producción.
 * Devuelve los ids creados, en orden.
 */
function seed_feed_bikes(PDO $pdo, int $userId, int $count, int $startMinute = 0): array
{
    $stmt = $pdo->prepare(
        'INSERT INTO bikes (user_id, title, brand, `condition`, price, currency, description, created_at)
         VALUES (:user_id, :title, :brand, "used", 1000.00, "USD", "Moto de prueba para el feed.", :created_at)'
    );

    $ids = [];
    for ($i = 0; $i < $count; $i++) {
        $minute = $startMinute + $i;
        $stmt->execute([
            'user_id' => $userId,
            'title' => 'Moto feed ' . $minute,
            'brand' => 'Yamaha',
            'created_at' => date('Y-m-d H:i:s', strtotime('2026-09-01 00:00:00') + $minute * 60),
        ]);
        $ids[] = (int) $pdo->lastInsertId();
    }

    return $ids;
}

/**
 * Pide el JSON del endpoint como lo haría el navegador (RNF-3).
 * El endpoint usa db_connect() (la base real de la app), así que durante la
 * llamada se lo apunta a la base de tests con BIKE_CENTER_DB.
 */
function call_feed_api(?int $before): array
{
    putenv('BIKE_CENTER_DB=bike_center_test');
    $_GET = $before === null ? [] : ['before' => (string) $before];
    ob_start();
    include dirname(__DIR__, 2) . '/public/api/bikes.php';
    $raw = ob_get_clean();
    putenv('BIKE_CENTER_DB');

    $data = json_decode($raw, true);
    expect(json_last_error())->toBe(JSON_ERROR_NONE, 'El endpoint debe devolver JSON válido');

    return $data;
}

describe('list_bikes_feed (RF-4, RNF-3)', function () {
    it('entrega 3 tandas de 20 sin repetir ni perder motos aunque se publiquen nuevas en el medio', function () {
        $db = test_db();
        $userId = feed_owner_id();
        $seeded = seed_feed_bikes($db, $userId, 45);

        // Tanda 1: las 20 más recientes.
        $page1 = list_bikes_feed($db, [], 20, null);
        expect(count($page1))->toBe(20);
        $ids1 = array_column($page1, 'id');

        // Mientras se recorren, otro vendedor publica 5 motos nuevas.
        $newIds = seed_feed_bikes($db, $userId, 5, 500);

        $page2 = list_bikes_feed($db, [], 20, $ids1[19]);
        $page3 = list_bikes_feed($db, [], 20, (int) $page2[19]['id']);

        $all = array_merge($ids1, array_column($page2, 'id'), array_column($page3, 'id'));
        $descending = $all;
        rsort($descending);

        // Sin repetidos, sin perder ninguna de las 45 y en orden descendente.
        expect($all)->toBe(array_values(array_unique($all)))
            ->and(array_diff($seeded, $all))->toBe([])
            ->and($all)->toBe($descending);

        // Las 5 nuevas no expulsan ni duplican: quedan fuera del recorrido.
        expect(array_intersect($all, $newIds))->toBe([]);
    });

    it('editar una moto no cambia su posición en el feed', function () {
        $db = test_db();
        $userId = feed_owner_id();
        seed_feed_bikes($db, $userId, 25);

        $before = array_column(list_bikes_feed($db, [], 20, null), 'id');

        // Simula la edición del futuro edit-bike: cambia datos, jamás created_at.
        $stmt = $db->prepare("UPDATE bikes SET title = 'Editada', price = 9999.99, `condition` = 'new' WHERE id = :id");
        $stmt->execute(['id' => $before[5]]);

        $after = array_column(list_bikes_feed($db, [], 20, null), 'id');

        expect($after)->toBe($before);
    });

    it('incluye la foto principal: la de posición 0, no la recién insertada', function () {
        $db = test_db();
        $bikeId = seed_feed_bikes($db, feed_owner_id(), 1)[0];

        $stmt = $db->prepare('INSERT INTO photos (bike_id, filename, position) VALUES (:bike_id, :filename, :position)');
        $stmt->execute(['bike_id' => $bikeId, 'filename' => 'segunda.png', 'position' => 1]);
        $stmt->execute(['bike_id' => $bikeId, 'filename' => 'primera.jpg', 'position' => 0]);

        $rows = list_bikes_feed($db, [], 20, null);

        expect($rows[0]['photo'])->toBe('primera.jpg');
    });

    it('devuelve la fila con photo en null si la moto no tiene fotos', function () {
        // Solo posible a mano: el formulario exige mínimo 1 foto (RF-3).
        $db = test_db();
        seed_feed_bikes($db, feed_owner_id(), 1);

        $rows = list_bikes_feed($db, [], 20, null);

        expect($rows[0]['photo'])->toBeNull();
    });
});

describe('api/bikes.php (RNF-3)', function () {
    it('devuelve la primera tanda con has_more cuando siguen habiendo motos', function () {
        seed_feed_bikes(test_db(), feed_owner_id(), 27);

        $first = call_feed_api(null);

        expect(count($first['bikes']))->toBe(20)
            ->and($first['has_more'])->toBeTrue()
            ->and($first['bikes'][0])->toHaveKeys(['id', 'title', 'brand', 'price', 'currency', 'status', 'photo', 'url']);
    });

    it('incluye la foto principal para que la tarjeta por scroll también la tenga', function () {
        $db = test_db();
        $bikeId = seed_feed_bikes($db, feed_owner_id(), 1)[0];

        $stmt = $db->prepare('INSERT INTO photos (bike_id, filename, position) VALUES (:bike_id, :filename, 0)');
        $stmt->execute(['bike_id' => $bikeId, 'filename' => 'una.jpg']);

        $data = call_feed_api(null);

        expect($data['bikes'][0]['photo'])->toBe('una.jpg');
    });

    it('siguiendo el cursor devuelve el resto y apaga has_more', function () {
        seed_feed_bikes(test_db(), feed_owner_id(), 27);

        $first = call_feed_api(null);
        $lastId = (int) $first['bikes'][19]['id'];
        $second = call_feed_api($lastId);

        expect(count($second['bikes']))->toBe(7)
            ->and($second['has_more'])->toBeFalse();
    });

    it('con la vitrina vacía devuelve lista vacía y has_more apagado', function () {
        $empty = call_feed_api(null);

        expect($empty['bikes'])->toBe([])
            ->and($empty['has_more'])->toBeFalse();
    });
});
