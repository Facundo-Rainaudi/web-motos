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

/**
 * Quita UNA foto de una publicación al editarla (RF-6).
 * Dos reglas blindadas acá en el servidor, no solo en la pantalla:
 * - la foto tiene que pertenecer a esa publicación (el WHERE doble evita que
 *   adivinando un id se borre una foto ajena), y
 * - la publicación nunca se queda sin fotos (hay que poder verla en la
 *   vitrina; el formulario solo muestra "Quitar" mientras haya más de una).
 * Devuelve ['ok' => true, 'filename' => ...] con el nombre del archivo para
 * que quien llamá borre también el archivo del disco, o ['ok' => false, ...].
 */
function delete_photo_of_bike(PDO $pdo, int $bikeId, int $photoId): array
{
    $stmt = $pdo->prepare('SELECT bike_id, filename FROM photos WHERE id = :id');
    $stmt->execute(['id' => $photoId]);
    $photo = $stmt->fetch();

    if ($photo === false || (int) $photo['bike_id'] !== $bikeId) {
        return ['ok' => false, 'error' => 'La foto no pertenece a esta publicación.'];
    }

    if (count(list_photos($pdo, $bikeId)) <= 1) {
        return ['ok' => false, 'error' => 'La publicación necesita al menos una foto.'];
    }

    $delete = $pdo->prepare('DELETE FROM photos WHERE id = :id AND bike_id = :bike_id');
    $delete->execute(['id' => $photoId, 'bike_id' => $bikeId]);

    return ['ok' => true, 'filename' => (string) $photo['filename']];
}

/**
 * Siguiente posición libre para una foto de la moto: la de posición más alta
 * más uno. No se puede calcular con count(): al quitar fotos quedan huecos
 * en la numeración y se producirían dos fotos empatadas en el mismo orden.
 * La usan las páginas al agregar fotos y update_bike_with_photos().
 */
function next_photo_position(PDO $pdo, int $bikeId): int
{
    $stmt = $pdo->prepare(
        'SELECT COALESCE(MAX(position), -1) + 1 FROM photos WHERE bike_id = :bike_id'
    );
    $stmt->execute(['bike_id' => $bikeId]);

    return (int) $stmt->fetchColumn();
}

/**
 * Reemplaza el archivo de UNA foto conservando su id y su posición (RF-6).
 * Es la salida para la publicación con una sola foto: como no se puede quitar
 * (no puede quedar en cero), se cambia el archivo de esa misma foto.
 * Solo se actualiza el nombre de archivo: la fila nunca desaparece, así que
 * la publicación no pasa ni un instante sin fotos.
 * Devuelve ['ok' => true, 'filename' => nombre_viejo] para que la página
 * borre el archivo anterior del disco, o ['ok' => false, 'error'] si la foto
 * no pertenece a la publicación.
 */
function replace_photo_of_bike(PDO $pdo, int $bikeId, int $photoId, string $newFilename): array
{
    $stmt = $pdo->prepare('SELECT bike_id, filename FROM photos WHERE id = :id');
    $stmt->execute(['id' => $photoId]);
    $photo = $stmt->fetch();

    if ($photo === false || (int) $photo['bike_id'] !== $bikeId) {
        return ['ok' => false, 'error' => 'La foto no pertenece a esta publicación.'];
    }

    $update = $pdo->prepare(
        'UPDATE photos SET filename = :filename WHERE id = :id AND bike_id = :bike_id'
    );
    $update->execute(['filename' => $newFilename, 'id' => $photoId, 'bike_id' => $bikeId]);

    return ['ok' => true, 'filename' => (string) $photo['filename']];
}
