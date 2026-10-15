<?php
// src/models/bike_model.php — todo el SQL de las motos.
// Acá vive el listado para la vitrina, el panel y las operaciones de dueño.

declare(strict_types=1);

/**
 * Crea una moto con sus fotos en UNA transacción (RF-3): o se guarda todo o
 * no queda nada en la base. Si algo falla a mitad de camino se deshace todo y
 * se propaga el error; quien llamó se encarga de borrar los archivos de fotos
 * que ya se hubieran movido al disco.
 *
 * $data trae los datos ya validados: title, brand, condition, price, currency
 * y description. $photos trae ['filename' => ...] y su posición se toma del
 * orden del arreglo (0 a 3).
 */
function create_bike(PDO $pdo, int $userId, array $data, array $photos): int
{
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO bikes (user_id, title, brand, `condition`, price, currency, description)
             VALUES (:user_id, :title, :brand, :condition, :price, :currency, :description)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'title' => $data['title'],
            'brand' => $data['brand'],
            'condition' => $data['condition'],
            'price' => $data['price'],
            'currency' => $data['currency'],
            'description' => $data['description'],
        ]);

        $bikeId = (int) $pdo->lastInsertId();

        foreach ($photos as $position => $photo) {
            insert_photo($pdo, $bikeId, $photo['filename'], $position);
        }

        $pdo->commit();

        return $bikeId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
