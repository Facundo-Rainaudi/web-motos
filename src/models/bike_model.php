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

/**
 * Tanda del feed de la vitrina (RF-4, RNF-3): más reciente primero, con
 * orden `created_at DESC` y desempate por `id DESC`.
 *
 * La paginación es por clave: si llega $beforeId, solo se devuelven motos con
 * id menor, así las publicadas mientras se recorre la lista no duplican ni
 * expulsan las ya vistas. $limit es un entero de código (nunca del usuario)
 * y se recorta a un rango razonable antes de pegarle al SQL.
 */
function list_bikes_feed(PDO $pdo, int $limit = 20, ?int $beforeId = null): array
{
    $limit = max(1, min(50, $limit));

    // La foto principal es la de posición 0; si la moto no tiene fotos, el
    // campo viene en null y la tarjeta se arma sin imagen.
    $sql = 'SELECT b.id, b.title, b.brand, b.`condition`, b.price, b.currency, b.status, b.created_at,
                   (SELECT p.filename FROM photos p
                    WHERE p.bike_id = b.id
                    ORDER BY p.position
                    LIMIT 1) AS photo
            FROM bikes b';
    $params = [];

    if ($beforeId !== null) {
        $sql .= ' WHERE b.id < :before_id';
        $params['before_id'] = $beforeId;
    }

    $sql .= ' ORDER BY b.created_at DESC, b.id DESC LIMIT ' . $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/**
 * Una moto con el nombre de su dueño, para el detalle (RF-5).
 * Devuelve null si la moto no existe o fue borrada: en ambos casos el detalle
 * muestra "no disponible" sin filtrar ningún otro dato.
 */
function find_bike_with_owner(PDO $pdo, int $bikeId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT b.id, b.user_id, b.title, b.brand, b.`condition`, b.price, b.currency,
                b.description, b.status, b.created_at, u.full_name AS seller_name
         FROM bikes b
         JOIN users u ON u.id = b.user_id
         WHERE b.id = :id'
    );
    $stmt->execute(['id' => $bikeId]);
    $bike = $stmt->fetch();

    return $bike === false ? null : $bike;
}
