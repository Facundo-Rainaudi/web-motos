<?php
// tests/Integration/BikeTest.php
// Pruebas de integración de crear una moto con sus fotos (RF-3) contra
// bike_center_test: o se guarda todo en una transacción o no queda nada.

declare(strict_types=1);

/**
 * Cuenta de prueba y datos válidos de una moto, para repetir en cada test.
 */
function seed_owner_and_data(): array
{
    $user = register_user(test_db(), 'Ana Perez', 'ana@x.com', 'moto2026', '1155555555');

    $data = [
        'title' => 'Yamaha FZ 2.0',
        'brand' => 'Yamaha',
        'condition' => 'used',
        'price' => '2500.00',
        'currency' => 'USD',
        'description' => 'Moto con muy pocos kilómetros.',
    ];

    return ['user_id' => $user['user_id'], 'data' => $data];
}

describe('create_bike (RF-3)', function () {
    it('crea la moto con sus 4 fotos y la posición de cada una', function () {
        ['user_id' => $userId, 'data' => $data] = seed_owner_and_data();
        $photos = [
            ['filename' => 'uno.jpg'],
            ['filename' => 'dos.jpg'],
            ['filename' => 'tres.jpg'],
            ['filename' => 'cuatro.jpg'],
        ];

        $bikeId = create_bike(test_db(), $userId, $data, $photos);

        // La moto quedó asociada a su dueño con los datos guardados.
        $stmt = test_db()->prepare('SELECT * FROM bikes WHERE id = :id');
        $stmt->execute(['id' => $bikeId]);
        $bike = $stmt->fetch();

        expect((int) $bike['user_id'])->toBe($userId)
            ->and($bike['title'])->toBe('Yamaha FZ 2.0')
            ->and($bike['condition'])->toBe('used')
            ->and((float) $bike['price'])->toBe(2500.0)
            ->and($bike['currency'])->toBe('USD')
            ->and($bike['status'])->toBe('available');

        // Las fotos quedaron en orden (posición 0 a 3).
        $stmt = test_db()->prepare('SELECT filename, position FROM photos WHERE bike_id = :id ORDER BY position');
        $stmt->execute(['id' => $bikeId]);
        $rows = $stmt->fetchAll();

        expect(array_column($rows, 'filename'))->toBe(['uno.jpg', 'dos.jpg', 'tres.jpg', 'cuatro.jpg'])
            ->and(array_map('intval', array_column($rows, 'position')))->toBe([0, 1, 2, 3]);
    });

    it('no deja registros huérfanos si una foto falla a mitad de la transacción', function () {
        ['user_id' => $userId, 'data' => $data] = seed_owner_and_data();
        // La segunda foto tiene un nombre de 300 caracteres: MySQL lo rechaza
        // (la columna admite 190), lo que fuerza el error a mitad del guardado.
        $photos = [
            ['filename' => 'ok.jpg'],
            ['filename' => str_repeat('x', 300)],
        ];

        $failed = false;
        try {
            create_bike(test_db(), $userId, $data, $photos);
        } catch (Throwable $e) {
            $failed = true;
        }

        expect($failed)->toBeTrue()
            ->and((int) test_db()->query('SELECT COUNT(*) FROM bikes')->fetchColumn())->toBe(0)
            ->and((int) test_db()->query('SELECT COUNT(*) FROM photos')->fetchColumn())->toBe(0);
    });

    it('no crea la moto si el dueño no existe (clave foránea)', function () {
        ['data' => $data] = seed_owner_and_data();
        $photos = [['filename' => 'una.jpg']];

        $failed = false;
        try {
            create_bike(test_db(), 999999, $data, $photos);
        } catch (Throwable $e) {
            $failed = true;
        }

        expect($failed)->toBeTrue()
            ->and((int) test_db()->query('SELECT COUNT(*) FROM bikes')->fetchColumn())->toBe(0);
    });
});
