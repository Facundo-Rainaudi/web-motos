<?php
// src/models/photo_model.php — todo el SQL de las fotos de una moto.

declare(strict_types=1);

/**
 * Inserta una foto de una moto en su posición (0 a 3).
 * Se llama desde create_bike(), dentro de la transacción.
 */
function insert_photo(PDO $pdo, int $bikeId, string $filename, int $position): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO photos (bike_id, filename, position)
         VALUES (:bike_id, :filename, :position)'
    );
    $stmt->execute([
        'bike_id' => $bikeId,
        'filename' => $filename,
        'position' => $position,
    ]);
}

/**
 * Devuelve las fotos de una moto en el orden en que se muestran.
 */
function list_photos(PDO $pdo, int $bikeId): array
{
    $stmt = $pdo->prepare(
        'SELECT id, filename, position
         FROM photos
         WHERE bike_id = :bike_id
         ORDER BY position'
    );
    $stmt->execute(['bike_id' => $bikeId]);

    return $stmt->fetchAll();
}
