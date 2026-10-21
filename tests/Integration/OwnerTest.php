<?php
// tests/Integration/OwnerTest.php
// Gestión del dueño (RF-6 a RF-10, historias H6–H8): mis publicaciones,
// editar datos y fotos, borrar con cascada de archivos y alternar
// vendida/disponible. Todas las operaciones verifican el dueño en el
// servidor: un usuario ajeno no logra ningún cambio (RF-10).

declare(strict_types=1);

/**
 * Dueño y moto de prueba con dos archivos de fotos reales en una carpeta
 * temporal: permite comprobar que el borrado limpia la base Y el disco.
 * Devuelve user_id, bike_id y la carpeta (que el test debe limpiar).
 */
function seed_owner_bike_with_files(): array
{
    ['user_id' => $userId, 'data' => $data] = seed_owner_and_data();

    $dir = sys_get_temp_dir() . '/bc_owner_' . bin2hex(random_bytes(6));
    mkdir($dir);
    file_put_contents($dir . '/foto_uno.jpg', 'contenido uno');
    file_put_contents($dir . '/foto_dos.jpg', 'contenido dos');

    $bikeId = create_bike(test_db(), $userId, $data, [
        ['filename' => 'foto_uno.jpg'],
        ['filename' => 'foto_dos.jpg'],
    ]);

    return ['user_id' => $userId, 'bike_id' => $bikeId, 'dir' => $dir];
}

/**
 * Un segundo usuario, para las pruebas de permiso (RF-10).
 */
function seed_foreign_user_id(): int
{
    return (int) register_user(test_db(), 'Otro Usuario', 'otro@x.com', 'moto2026', '1133333333')['user_id'];
}

describe('list_bikes_by_user (RF-9)', function () {
    it('devuelve solo las motos del dueño, con su foto principal y su estado', function () {
        $db = test_db();
        $ownerId = feed_owner_id();

        // Una moto propia con dos fotos: la principal es la de posición 0.
        $ownId = seed_feed_bikes($db, $ownerId, 1)[0];
        $stmt = $db->prepare('INSERT INTO photos (bike_id, filename, position) VALUES (:bike_id, :filename, :position)');
        $stmt->execute(['bike_id' => $ownId, 'filename' => 'principal.jpg', 'position' => 0]);
        $stmt->execute(['bike_id' => $ownId, 'filename' => 'extra.jpg', 'position' => 1]);

        // Una moto ajena: no debe aparecer en el listado.
        seed_feed_bikes($db, seed_foreign_user_id(), 1);

        $rows = list_bikes_by_user($db, $ownerId);

        expect(count($rows))->toBe(1)
            ->and((int) $rows[0]['id'])->toBe($ownId)
            ->and($rows[0]['photo'])->toBe('principal.jpg')
            ->and($rows[0]['status'])->toBe('available');
    });

    it('devuelve la lista vacía para un usuario sin publicaciones', function () {
        expect(list_bikes_by_user(test_db(), seed_foreign_user_id()))->toBe([]);
    });
});

describe('update_bike (RF-6)', function () {
    it('cambia los datos sin tocar la posición en el feed ni el estado de vendida', function () {
        $db = test_db();
        $ownerId = feed_owner_id();
        $ids = seed_feed_bikes($db, $ownerId, 3);

        $db->prepare("UPDATE bikes SET status = 'sold' WHERE id = :id")->execute(['id' => $ids[1]]);
        $before = array_column(list_bikes_feed($db, [], 20, null), 'id');
        $createdAtBefore = $db->query('SELECT created_at FROM bikes WHERE id = ' . $ids[1])->fetchColumn();

        update_bike($db, $ids[1], [
            'title' => 'Título editado',
            'brand' => 'Zanella',
            'condition' => 'new',
            'price' => '9999.99',
            'currency' => 'ARS',
            'description' => 'Descripción editada por el dueño.',
        ]);

        $row = $db->query('SELECT * FROM bikes WHERE id = ' . $ids[1])->fetch();

        // Los datos cambiaron, pero ni el orden del feed ni el estado.
        expect(array_column(list_bikes_feed($db, [], 20, null), 'id'))->toBe($before)
            ->and($row['title'])->toBe('Título editado')
            ->and($row['currency'])->toBe('ARS')
            ->and($row['status'])->toBe('sold')
            ->and($row['created_at'])->toBe($createdAtBefore);
    });

    it('edita sin problema una moto que ya está vendida (RF-6)', function () {
        $db = test_db();
        $ownerId = feed_owner_id();
        $bikeId = seed_feed_bikes($db, $ownerId, 1)[0];

        $db->prepare("UPDATE bikes SET status = 'sold' WHERE id = :id")->execute(['id' => $bikeId]);

        update_bike($db, $bikeId, [
            'title' => 'Editada estando vendida',
            'brand' => 'Yamaha',
            'condition' => 'used',
            'price' => '1500.00',
            'currency' => 'USD',
            'description' => 'Se puede editar igual.',
        ]);

        expect($db->query('SELECT title FROM bikes WHERE id = ' . $bikeId)->fetchColumn())->toBe('Editada estando vendida');
    });

    it('update_bike_with_photos actualiza los datos y agrega la foto en una sola transacción', function () {
        ['user_id' => $userId, 'data' => $data] = seed_owner_and_data();
        $db = test_db();
        $bikeId = create_bike($db, $userId, $data, [['filename' => 'original.jpg']]);

        $edited = $data;
        $edited['title'] = 'Con foto nueva';
        update_bike_with_photos($db, $bikeId, $edited, [['filename' => 'nueva.jpg']]);

        expect($db->query('SELECT title FROM bikes WHERE id = ' . $bikeId)->fetchColumn())->toBe('Con foto nueva')
            ->and(array_column(list_photos($db, $bikeId), 'filename'))->toBe(['original.jpg', 'nueva.jpg']);
    });

    it('deshace los datos y la foto si el guardado falla a mitad de la transacción', function () {
        ['user_id' => $userId, 'data' => $data] = seed_owner_and_data();
        $db = test_db();
        $bikeId = create_bike($db, $userId, $data, [['filename' => 'original.jpg']]);

        $edited = $data;
        $edited['title'] = 'No debe quedar';
        // Nombre de 300 caracteres: MySQL lo rechaza y fuerza el rollback.
        $failed = false;
        try {
            update_bike_with_photos($db, $bikeId, $edited, [['filename' => str_repeat('x', 300)]]);
        } catch (Throwable $e) {
            $failed = true;
        }

        expect($failed)->toBeTrue()
            ->and($db->query('SELECT title FROM bikes WHERE id = ' . $bikeId)->fetchColumn())->toBe($data['title'])
            ->and(array_column(list_photos($db, $bikeId), 'filename'))->toBe(['original.jpg']);
    });

    it('la foto nueva sigue a la de posición más alta aunque haya huecos (regresión)', function () {
        // Tras quitar una foto quedan huecos (0, 2): la nueva no puede caer
        // en una posición ya ocupada, o el orden del carrusel empata.
        ['user_id' => $userId, 'data' => $data] = seed_owner_and_data();
        $db = test_db();
        $bikeId = create_bike($db, $userId, $data, [
            ['filename' => 'cero.jpg'],
            ['filename' => 'uno.jpg'],
            ['filename' => 'dos.jpg'],
        ]);
        delete_photo_of_bike($db, $bikeId, (int) list_photos($db, $bikeId)[0]['id']);

        update_bike_with_photos($db, $bikeId, $data, [['filename' => 'nueva.jpg']]);

        $rows = list_photos($db, $bikeId);
        $positions = array_map('intval', array_column($rows, 'position'));

        expect(array_column($rows, 'filename'))->toBe(['uno.jpg', 'dos.jpg', 'nueva.jpg'])
            ->and($positions)->toBe([1, 2, 3])
            ->and(array_unique($positions))->toHaveCount(3);
    });
});

describe('delete_photo_of_bike (RF-6)', function () {
    it('quita la foto pedida y devuelve su nombre para borrar el archivo', function () {
        ['user_id' => $userId, 'data' => $data] = seed_owner_and_data();
        $db = test_db();
        $bikeId = create_bike($db, $userId, $data, [
            ['filename' => 'primera.jpg'],
            ['filename' => 'segunda.jpg'],
        ]);
        $secondId = (int) list_photos($db, $bikeId)[1]['id'];

        $result = delete_photo_of_bike($db, $bikeId, $secondId);

        expect($result)->toBe(['ok' => true, 'filename' => 'segunda.jpg'])
            ->and(array_column(list_photos($db, $bikeId), 'filename'))->toBe(['primera.jpg']);
    });

    it('no deja que la publicación se quede sin fotos', function () {
        ['user_id' => $userId, 'data' => $data] = seed_owner_and_data();
        $db = test_db();
        $bikeId = create_bike($db, $userId, $data, [['filename' => 'unica.jpg']]);
        $onlyId = (int) list_photos($db, $bikeId)[0]['id'];

        $result = delete_photo_of_bike($db, $bikeId, $onlyId);

        expect($result['ok'])->toBeFalse()
            ->and($result['error'])->toContain('al menos una foto')
            ->and(array_column(list_photos($db, $bikeId), 'filename'))->toBe(['unica.jpg']);
    });

    it('rechaza una foto que no pertenece a la publicación', function () {
        ['user_id' => $ownerId, 'data' => $data] = seed_owner_and_data();
        $db = test_db();
        $bikeA = create_bike($db, $ownerId, $data, [
            ['filename' => 'a1.jpg'],
            ['filename' => 'a2.jpg'],
        ]);
        $bikeB = create_bike($db, $ownerId, $data, [['filename' => 'b1.jpg']]);
        $photoOfB = (int) list_photos($db, $bikeB)[0]['id'];

        $result = delete_photo_of_bike($db, $bikeA, $photoOfB);

        expect($result['ok'])->toBeFalse()
            ->and(array_column(list_photos($db, $bikeB), 'filename'))->toBe(['b1.jpg']);
    });
});

describe('replace_photo_of_bike (RF-6)', function () {
    it('cambia el archivo conservando la foto, su posición y devolviendo el viejo', function () {
        ['user_id' => $userId, 'data' => $data] = seed_owner_and_data();
        $db = test_db();
        $bikeId = create_bike($db, $userId, $data, [
            ['filename' => 'vieja.jpg'],
            ['filename' => 'otra.jpg'],
        ]);
        $photoId = (int) list_photos($db, $bikeId)[0]['id'];

        $result = replace_photo_of_bike($db, $bikeId, $photoId, 'nueva.jpg');

        $rows = list_photos($db, $bikeId);
        expect($result)->toBe(['ok' => true, 'filename' => 'vieja.jpg'])
            ->and(array_column($rows, 'filename'))->toBe(['nueva.jpg', 'otra.jpg'])
            ->and((int) $rows[0]['id'])->toBe($photoId)
            ->and((int) $rows[0]['position'])->toBe(0);
    });

    it('rechaza una foto que no pertenece a la publicación', function () {
        ['user_id' => $userId, 'data' => $data] = seed_owner_and_data();
        $db = test_db();
        $bikeA = create_bike($db, $userId, $data, [
            ['filename' => 'a1.jpg'],
            ['filename' => 'a2.jpg'],
        ]);
        $bikeB = create_bike($db, $userId, $data, [['filename' => 'b1.jpg']]);
        $photoOfB = (int) list_photos($db, $bikeB)[0]['id'];

        $result = replace_photo_of_bike($db, $bikeA, $photoOfB, 'hack.jpg');

        expect($result['ok'])->toBeFalse()
            ->and(array_column(list_photos($db, $bikeB), 'filename'))->toBe(['b1.jpg']);
    });
});

describe('delete_bike_as_owner (RF-7, RF-10)', function () {
    it('el dueño borra la moto, sus fotos en la base y sus archivos del disco', function () {
        ['user_id' => $userId, 'bike_id' => $bikeId, 'dir' => $dir] = seed_owner_bike_with_files();

        $result = delete_bike_as_owner(test_db(), $bikeId, $userId, $dir);

        expect($result['ok'])->toBeTrue()
            ->and(list_photos(test_db(), $bikeId))->toBe([])
            ->and((int) test_db()->query('SELECT COUNT(*) FROM bikes')->fetchColumn())->toBe(0)
            ->and(is_file($dir . '/foto_uno.jpg'))->toBeFalse()
            ->and(is_file($dir . '/foto_dos.jpg'))->toBeFalse();

        rmdir($dir);
    });

    it('un usuario ajeno no logra borrar: la moto, las fotos y los archivos quedan igual', function () {
        ['user_id' => $userId, 'bike_id' => $bikeId, 'dir' => $dir] = seed_owner_bike_with_files();

        $result = delete_bike_as_owner(test_db(), $bikeId, seed_foreign_user_id(), $dir);

        expect($result['ok'])->toBeFalse()
            ->and($result['error'])->toContain('dueño')
            ->and((int) test_db()->query('SELECT COUNT(*) FROM bikes')->fetchColumn())->toBe(1)
            ->and(count(list_photos(test_db(), $bikeId)))->toBe(2)
            ->and(is_file($dir . '/foto_uno.jpg'))->toBeTrue()
            ->and(is_file($dir . '/foto_dos.jpg'))->toBeTrue();

        unlink($dir . '/foto_uno.jpg');
        unlink($dir . '/foto_dos.jpg');
        rmdir($dir);
    });

    it('responde con error si la moto no existe o ya fue borrada', function () {
        ['user_id' => $userId] = seed_owner_bike_with_files();

        $result = delete_bike_as_owner(test_db(), 999999, $userId);

        expect($result['ok'])->toBeFalse()
            ->and($result['error'])->toContain('no existe');
    });
});

describe('toggle_bike_status_as_owner (RF-8, RF-10)', function () {
    it('alterna disponible → vendida → disponible y la vitrina muestra el estado', function () {
        $db = test_db();
        $ownerId = feed_owner_id();
        $bikeId = seed_feed_bikes($db, $ownerId, 1)[0];

        $sold = toggle_bike_status_as_owner($db, $bikeId, $ownerId);
        expect($sold)->toBe(['ok' => true, 'status' => 'sold'])
            ->and(list_bikes_feed($db, [], 20, null)[0]['status'])->toBe('sold');

        // La reversibilidad (RF-8): de vuelta a disponible, sin errores.
        $back = toggle_bike_status_as_owner($db, $bikeId, $ownerId);
        expect($back)->toBe(['ok' => true, 'status' => 'available'])
            ->and(list_bikes_feed($db, [], 20, null)[0]['status'])->toBe('available');
    });

    it('dos marcas seguidas vuelven a vender la moto sin error', function () {
        $db = test_db();
        $ownerId = feed_owner_id();
        $bikeId = seed_feed_bikes($db, $ownerId, 1)[0];

        toggle_bike_status_as_owner($db, $bikeId, $ownerId); // → sold
        $again = toggle_bike_status_as_owner($db, $bikeId, $ownerId); // → available
        $third = toggle_bike_status_as_owner($db, $bikeId, $ownerId); // → sold

        expect($again['ok'])->toBeTrue()
            ->and($third)->toBe(['ok' => true, 'status' => 'sold']);
    });

    it('un usuario ajeno no puede cambiar el estado', function () {
        $db = test_db();
        $bikeId = seed_feed_bikes($db, feed_owner_id(), 1)[0];

        $result = toggle_bike_status_as_owner($db, $bikeId, seed_foreign_user_id());

        expect($result['ok'])->toBeFalse()
            ->and($result['error'])->toContain('dueño')
            ->and($db->query('SELECT status FROM bikes WHERE id = ' . $bikeId)->fetchColumn())->toBe('available');
    });

    it('responde con error si la moto no existe', function () {
        ['user_id' => $userId] = seed_owner_bike_with_files();

        expect(toggle_bike_status_as_owner(test_db(), 999999, $userId)['ok'])->toBeFalse();
    });
});
