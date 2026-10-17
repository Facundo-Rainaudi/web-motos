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

/**
 * Publicaciones de UN dueño para "Mis publicaciones" (RF-9, historia H7):
 * solo las suyas, más reciente primero, con su foto principal y su estado
 * (Disponible/Vendida). Trae el mismo formato que el feed para poder
 * reutilizar views/bike_card.php tal cual.
 */
function list_bikes_by_user(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT b.id, b.title, b.brand, b.`condition`, b.price, b.currency, b.status, b.created_at,
                   (SELECT p.filename FROM photos p
                    WHERE p.bike_id = b.id
                    ORDER BY p.position
                    LIMIT 1) AS photo
            FROM bikes b
            WHERE b.user_id = :user_id
            ORDER BY b.created_at DESC, b.id DESC'
    );
    $stmt->execute(['user_id' => $userId]);

    return $stmt->fetchAll();
}

/**
 * Actualiza los datos de una moto (RF-6, edición).
 * A propósito NO toca created_at (así la moto no se mueve en el feed) ni
 * status (la edición no cambia si está vendida; eso solo lo hace el dueño
 * con su botón de vender, RF-8).
 */
function update_bike(PDO $pdo, int $bikeId, array $data): void
{
    $stmt = $pdo->prepare(
        'UPDATE bikes
         SET title = :title, brand = :brand, `condition` = :condition,
             price = :price, currency = :currency, description = :description
         WHERE id = :id'
    );
    $stmt->execute([
        'title' => $data['title'],
        'brand' => $data['brand'],
        'condition' => $data['condition'],
        'price' => $data['price'],
        'currency' => $data['currency'],
        'description' => $data['description'],
        'id' => $bikeId,
    ]);
}

/**
 * Edita los datos Y agrega fotos nuevas en UNA transacción (RF-6), del mismo
 * modo que create_bike(): o se guarda todo o no cambia nada. Si algo falla a
 * mitad de camino se deshace todo y se propaga el error; quien llamó se
 * encarga de borrar del disco las fotos que ya hubiera movido.
 */
function update_bike_with_photos(PDO $pdo, int $bikeId, array $data, array $newPhotos): void
{
    $pdo->beginTransaction();

    try {
        update_bike($pdo, $bikeId, $data);

        // Las fotos nuevas siguen a la existente de posición más alta.
        $nextPosition = next_photo_position($pdo, $bikeId);

        foreach ($newPhotos as $i => $photo) {
            insert_photo($pdo, $bikeId, $photo['filename'], $nextPosition + $i);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Borra una publicación de su dueño (RF-7, RF-10): la moto, sus fotos en la
 * base (por cascada) y sus archivos del disco.
 * La verificación de dueño es SIEMPRE acá, en el servidor: el id del dueño
 * sale de la base, jamás del formulario. Un usuario ajeno recibe un error y
 * no se borra nada. Devuelve ['ok' => true] o ['ok' => false, 'error'].
 * $photoDir solo lo pasan los tests para apuntar a una carpeta temporal.
 */
function delete_bike_as_owner(PDO $pdo, int $bikeId, int $actorId, string $photoDir = ''): array
{
    $bike = find_bike_with_owner($pdo, $bikeId);

    if ($bike === null) {
        return ['ok' => false, 'error' => 'La publicación no existe o ya fue borrada.'];
    }

    if ((int) $bike['user_id'] !== $actorId) {
        return ['ok' => false, 'error' => 'Solo el dueño puede borrar esta publicación.'];
    }

    // Los nombres se guardan antes de borrar: el DELETE de la moto borra
    // también sus filas de fotos (cascada del esquema, RF-7).
    $filenames = array_column(list_photos($pdo, $bikeId), 'filename');

    $stmt = $pdo->prepare('DELETE FROM bikes WHERE id = :id');
    $stmt->execute(['id' => $bikeId]);

    // Los archivos del disco se borran recién con la base ya limpia: si algo
    // fallara acá quedaría algún archivo huérfano, pero nunca una fila sin
    // archivo (lo contrario rompería la vitrina).
    foreach ($filenames as $filename) {
        delete_photo_file($filename, $photoDir);
    }

    return ['ok' => true];
}

/**
 * Alterna el estado de la moto de su dueño (RF-8, RF-10): de disponible a
 * vendida y de vuelta. Como lee el estado actual en cada llamada, marcar dos
 * veces seguidas simplemente alterna, sin errores. Devuelve
 * ['ok' => true, 'status' => nuevo] o ['ok' => false, 'error'].
 */
function toggle_bike_status_as_owner(PDO $pdo, int $bikeId, int $actorId): array
{
    $bike = find_bike_with_owner($pdo, $bikeId);

    if ($bike === null) {
        return ['ok' => false, 'error' => 'La publicación no existe o ya fue borrada.'];
    }

    if ((int) $bike['user_id'] !== $actorId) {
        return ['ok' => false, 'error' => 'Solo el dueño puede cambiar el estado de esta publicación.'];
    }

    $newStatus = $bike['status'] === 'sold' ? 'available' : 'sold';

    $stmt = $pdo->prepare('UPDATE bikes SET status = :status WHERE id = :id');
    $stmt->execute(['status' => $newStatus, 'id' => $bikeId]);

    return ['ok' => true, 'status' => $newStatus];
}
