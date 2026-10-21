<?php
// tests/Integration/SearchTest.php
// Spec 02 (RF-11, RF-12, RF-13 en su parte de modelo): el listado filtrado
// busca por título o marca sin distinguir mayúsculas ni acentos, aplica
// cada filtro solo y todos combinados, muestra las vendidas por defecto y
// las excluye con status=available, mantiene el orden por creación, y el
// contador coincide con el listado. Los filtros llegan por parse_feed_filters
// como lo hace index.php, así que el parseo y el SQL se prueban juntos.

declare(strict_types=1);

/**
 * Dueño de prueba de las motos de búsqueda.
 */
function search_owner_id(): int
{
    return (int) register_user(test_db(), 'Search Owner', 'search@x.com', 'moto2026', '1155555555')['user_id'];
}

/**
 * Crea UNA moto con datos concretos. $order define la fecha de creación
 * (0 = la más vieja), así cada test controla el orden esperado del listado.
 */
function seed_search_bike(
    PDO $pdo,
    int $userId,
    int $order,
    string $title,
    string $brand,
    string $condition = 'used',
    float $price = 1000.0,
    string $currency = 'USD',
    string $status = 'available'
): int {
    $stmt = $pdo->prepare(
        'INSERT INTO bikes (user_id, title, brand, `condition`, price, currency, description, status, created_at)
         VALUES (:user_id, :title, :brand, :condition, :price, :currency, "Moto de prueba de búsqueda.", :status, :created_at)'
    );
    $stmt->execute([
        'user_id' => $userId,
        'title' => $title,
        'brand' => $brand,
        'condition' => $condition,
        'price' => number_format($price, 2, '.', ''),
        'currency' => $currency,
        'status' => $status,
        'created_at' => date('Y-m-d H:i:s', strtotime('2026-09-01 00:00:00') + $order * 60),
    ]);

    return (int) $pdo->lastInsertId();
}

/**
 * Pide el listado filtrado como lo hará index.php: los parámetros de la URL
 * pasan por parse_feed_filters y recién ahí llegan al modelo.
 * Devuelve solo los títulos, que es lo que comparan los tests.
 */
function search_titles(array $get, ?int $beforeId = null): array
{
    $rows = list_bikes_feed(test_db(), parse_feed_filters($get), 20, $beforeId);

    return array_column($rows, 'title');
}

describe('list_bikes_feed con filtros (RF-11, RF-12)', function () {
    it('busca por parte del título y por parte de la marca', function () {
        $db = test_db();
        $userId = search_owner_id();
        seed_search_bike($db, $userId, 0, 'Yamaha FZ 25', 'Yamaha');
        seed_search_bike($db, $userId, 1, 'Honda CB 190', 'Honda');
        seed_search_bike($db, $userId, 2, 'V7 Classic', 'Moto Guzzi');

        expect(search_titles(['q' => 'fz']))->toBe(['Yamaha FZ 25'])
            ->and(search_titles(['q' => 'guzzi']))->toBe(['V7 Classic']);
    });

    it('no distingue mayúsculas en la búsqueda', function () {
        $userId = search_owner_id();
        seed_search_bike(test_db(), $userId, 0, 'Yamaha FZ 25', 'Yamaha');

        expect(search_titles(['q' => 'FZ']))->toBe(['Yamaha FZ 25'])
            ->and(search_titles(['q' => 'YAMAHA']))->toBe(['Yamaha FZ 25']);
    });

    it('no distingue acentos en la búsqueda', function () {
        $userId = search_owner_id();
        seed_search_bike(test_db(), $userId, 0, 'Máxima 650', 'Benelli');

        expect(search_titles(['q' => 'maxima']))->toBe(['Máxima 650']);
    });

    it('devuelve vacío y contador cero cuando no hay coincidencias', function () {
        $userId = search_owner_id();
        seed_search_bike(test_db(), $userId, 0, 'Yamaha FZ 25', 'Yamaha');

        expect(search_titles(['q' => 'zzz']))->toBe([])
            ->and(count_bikes_matching(test_db(), parse_feed_filters(['q' => 'zzz'])))->toBe(0);
    });

    it('filtra por condición', function () {
        $db = test_db();
        $userId = search_owner_id();
        seed_search_bike($db, $userId, 0, 'Moto nueva A', 'Alpha', 'new');
        seed_search_bike($db, $userId, 1, 'Moto usada B', 'Beta', 'used');

        expect(search_titles(['condition' => 'new']))->toBe(['Moto nueva A'])
            ->and(search_titles(['condition' => 'used']))->toBe(['Moto usada B']);
    });

    it('filtra por moneda', function () {
        $db = test_db();
        $userId = search_owner_id();
        seed_search_bike($db, $userId, 0, 'En pesos', 'Alpha', 'used', 500000.0, 'ARS');
        seed_search_bike($db, $userId, 1, 'En dólares', 'Beta', 'used', 1000.0, 'USD');

        expect(search_titles(['currency' => 'USD']))->toBe(['En dólares'])
            ->and(search_titles(['currency' => 'ARS']))->toBe(['En pesos']);
    });

    it('filtra por rango de precio con los límites incluidos', function () {
        $db = test_db();
        $userId = search_owner_id();
        seed_search_bike($db, $userId, 0, 'Barata', 'Alpha', 'used', 500.0);
        seed_search_bike($db, $userId, 1, 'Media', 'Beta', 'used', 1000.0);
        seed_search_bike($db, $userId, 2, 'Cara', 'Gamma', 'used', 1500.0);

        expect(search_titles(['min' => '1000']))->toBe(['Cara', 'Media'])
            ->and(search_titles(['max' => '1000']))->toBe(['Media', 'Barata'])
            ->and(search_titles(['min' => '1000', 'max' => '1000']))->toBe(['Media']);
    });

    it('aplica búsqueda y todos los filtros a la vez', function () {
        $db = test_db();
        $userId = search_owner_id();
        seed_search_bike($db, $userId, 0, 'FZ 25 usada', 'Yamaha', 'used', 2000.0, 'USD');
        seed_search_bike($db, $userId, 1, 'FZ 25 nueva', 'Yamaha', 'new', 4000.0, 'USD');
        seed_search_bike($db, $userId, 2, 'FZ 25 en pesos', 'Yamaha', 'used', 2000000.0, 'ARS');

        $titles = search_titles([
            'q' => 'fz',
            'condition' => 'used',
            'currency' => 'USD',
            'min' => '1500',
            'max' => '2500',
            'status' => 'available',
        ]);

        expect($titles)->toBe(['FZ 25 usada']);
    });

    it('muestra las vendidas por defecto y las excluye con status=available', function () {
        $db = test_db();
        $userId = search_owner_id();
        seed_search_bike($db, $userId, 0, 'Disponible', 'Alpha', 'used', 1000.0, 'USD', 'available');
        seed_search_bike($db, $userId, 1, 'Vendida', 'Beta', 'used', 1000.0, 'USD', 'sold');

        expect(search_titles([]))->toBe(['Vendida', 'Disponible'])
            ->and(search_titles(['status' => 'available']))->toBe(['Disponible']);
    });

    it('el contador coincide siempre con el listado', function () {
        $db = test_db();
        $userId = search_owner_id();
        seed_search_bike($db, $userId, 0, 'Alpha 1', 'Alpha', 'used', 1000.0);
        seed_search_bike($db, $userId, 1, 'Alpha 2', 'Alpha', 'new', 2000.0);
        seed_search_bike($db, $userId, 2, 'Beta 1', 'Beta', 'used', 3000.0);

        expect(count_bikes_matching($db, parse_feed_filters([])))->toBe(3)
            ->and(count_bikes_matching($db, parse_feed_filters(['q' => 'alpha'])))->toBe(2)
            ->and(count_bikes_matching($db, parse_feed_filters(['condition' => 'used', 'currency' => 'USD'])))->toBe(2)
            ->and(count_bikes_matching($db, parse_feed_filters(['q' => 'zzz'])))->toBe(0);
    });

    it('sin parámetros entrega el listado completo de siempre, más reciente primero', function () {
        // Regresión del RF-4: la llamada sin filtros es la de antes.
        $db = test_db();
        $userId = search_owner_id();
        seed_search_bike($db, $userId, 0, 'Vieja', 'Alpha');
        seed_search_bike($db, $userId, 1, 'Nueva', 'Beta');

        expect(search_titles([]))->toBe(['Nueva', 'Vieja']);
    });

    it('combina el paginado por clave con los filtros activos', function () {
        $db = test_db();
        $userId = search_owner_id();
        $oldest = seed_search_bike($db, $userId, 0, 'Usada vieja', 'Alpha', 'used');
        $middle = seed_search_bike($db, $userId, 1, 'Usada del medio', 'Beta', 'used');
        seed_search_bike($db, $userId, 2, 'Nueva', 'Gamma', 'new');

        // Pidiendo antes de la "del medio" y filtrando usadas, sólo queda la
        // más vieja; la "Nueva" ni siquiera entra por el filtro.
        $titles = search_titles(['condition' => 'used'], $middle);

        expect($titles)->toBe(['Usada vieja']);
    });
});
