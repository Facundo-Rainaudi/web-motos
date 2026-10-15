<?php
// tests/Integration/PhotoTest.php
// Comportamiento mínimo de las fotos en la base (RF-3 y base de RF-7):
// se leen en el orden en que se guardaron y desaparecen con su moto.

declare(strict_types=1);

describe('fotos en la base', function () {
    it('lee las fotos de una moto en su posición', function () {
        ['user_id' => $userId, 'data' => $data] = seed_owner_and_data();
        $photos = [
            ['filename' => 'primera.jpg'],
            ['filename' => 'segunda.jpg'],
            ['filename' => 'tercera.jpg'],
        ];

        $bikeId = create_bike(test_db(), $userId, $data, $photos);
        $rows = list_photos(test_db(), $bikeId);

        expect(array_column($rows, 'filename'))->toBe(['primera.jpg', 'segunda.jpg', 'tercera.jpg']);
    });

    it('borra las fotos cuando se borra su moto (cascada)', function () {
        ['user_id' => $userId, 'data' => $data] = seed_owner_and_data();
        $bikeId = create_bike(test_db(), $userId, $data, [['filename' => 'unica.jpg']]);

        // Por ahora se borra directo desde la base; el borrado con interfaz
        // llega en la tarea 4.3.
        $stmt = test_db()->prepare('DELETE FROM bikes WHERE id = :id');
        $stmt->execute(['id' => $bikeId]);

        expect(list_photos(test_db(), $bikeId))->toBe([])
            ->and((int) test_db()->query('SELECT COUNT(*) FROM photos')->fetchColumn())->toBe(0);
    });
});
