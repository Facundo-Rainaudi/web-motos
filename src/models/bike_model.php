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
 * WHERE común del feed filtrado (spec 02, RF-11 y RF-12): recibe los filtros
 * ya normalizados por parse_feed_filters() y devuelve las condiciones y sus
 * parámetros atados. Cada filtro se agrega solo si viene informado; con un
 * arreglo vacío no se agrega nada y el listado queda igual que siempre
 * (RF-4 intacto, verificado por los tests de FeedTest).
 * El LIKE usa dos parámetros distintos porque PDO con prepares nativos no
 * repite nombres; el texto ya viene sin comodines (filters.php) y todos los
 * valores van atados, nunca concatenados (RNF-8).
 *
 * @return array{0: list<string>, 1: array<string, mixed>} [condiciones, params]
 */
function bikes_feed_where(array $filters): array
{
    $parts = [];
    $params = [];

    // Búsqueda por texto: coincide dentro del título O de la marca.
    if (($filters['q'] ?? null) !== null) {
        $parts[] = '(b.title LIKE :q_title OR b.brand LIKE :q_brand)';
        $params['q_title'] = '%' . $filters['q'] . '%';
        $params['q_brand'] = $params['q_title'];
    }

    if (($filters['condition'] ?? null) !== null) {
        $parts[] = 'b.`condition` = :condition';
        $params['condition'] = $filters['condition'];
    }

    if (($filters['currency'] ?? null) !== null) {
        $parts[] = 'b.currency = :currency';
        $params['currency'] = $filters['currency'];
    }

    if (($filters['min'] ?? null) !== null) {
        $parts[] = 'b.price >= :price_min';
        $params['price_min'] = $filters['min'];
    }

    if (($filters['max'] ?? null) !== null) {
        $parts[] = 'b.price <= :price_max';
        $params['price_max'] = $filters['max'];
    }

    // "Solo disponibles" (RF-12): predicado fijo de la base, sin dato de
    // usuario que atar; sin este filtro se siguen mostrando las vendidas
    // con su etiqueta (RF-4).
    if (($filters['status'] ?? null) === 'available') {
        $parts[] = "b.status = 'available'";
    }

    return [$parts, $params];
}

/**
 * Tanda del feed de la vitrina (RF-4, RNF-3): más reciente primero, con
 * orden `created_at DESC` y desempate por `id DESC`.
 *
 * La paginación es por clave: si llega $beforeId, solo se devuelven motos con
 * id menor, así las publicadas mientras se recorre la lista no duplican ni
 * expulsan las ya vistas. $limit es un entero de código (nunca del usuario)
 * y se recorta a un rango razonable antes de pegarle al SQL.
 *
 * $filters (spec 02) acota el listado: con un arreglo vacío el SQL es el de
 * siempre; con selección, se agregan las condiciones de bikes_feed_where()
 * y el paginado por clave sigue funcionando igual dentro del resultado
 * filtrado (RF-13).
 */
function list_bikes_feed(PDO $pdo, array $filters = [], int $limit = 20, ?int $beforeId = null): array
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

    [$whereParts, $params] = bikes_feed_where($filters);

    if ($beforeId !== null) {
        $whereParts[] = 'b.id < :before_id';
        $params['before_id'] = $beforeId;
    }

    if ($whereParts !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $whereParts);
    }

    $sql .= ' ORDER BY b.created_at DESC, b.id DESC LIMIT ' . $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/**
 * Cuántas motos cumplen la misma selección de la vitrina (spec 02, RF-13):
 * el contador "N motos encontradas". Usa el MISMO WHERE que list_bikes_feed,
 * así el número y el listado nunca pueden divergir.
 */
function count_bikes_matching(PDO $pdo, array $filters): int
{
    [$whereParts, $params] = bikes_feed_where($filters);

    $sql = 'SELECT COUNT(*) FROM bikes b';

    if ($whereParts !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $whereParts);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
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
