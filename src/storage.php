<?php
// src/storage.php — subida y borrado de fotos (RF-3 y RNF-5).
// Reglas: tipo REAL de imagen con finfo (un PDF renombrado a .jpg no pasa),
// hasta 2 MB inclusive, al menos 200×200, hasta 4 por publicación y ninguna
// repetida dentro de la misma publicación (comparación por hash sha1).
// Los archivos se guardan con nombre generado por el servidor: el nombre que
// eligió el usuario nunca llega al disco.

declare(strict_types=1);

/** Formatos admitidos: tipo real (finfo) => extensión del archivo. */
const PHOTO_MIME_EXTENSIONS = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

/** Peso máximo por foto: 2 MB inclusive. */
const PHOTO_MAX_BYTES = 2097152;

/** Medida mínima por foto. */
const PHOTO_MIN_WIDTH = 200;
const PHOTO_MIN_HEIGHT = 200;

/**
 * Carpeta donde viven las fotos, es decir public/uploads.
 */
function uploads_dir(): string
{
    return dirname(__DIR__) . '/public/uploads';
}

/**
 * Detecta el tipo REAL del archivo con finfo; devuelve la extensión que le
 * corresponde o null si no es un formato admitido.
 */
function photo_extension(string $tmpPath): ?string
{
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpPath);

    return PHOTO_MIME_EXTENSIONS[$mime] ?? null;
}

/**
 * Valida una foto individual (lo que viene en un item de $_FILES).
 * Devuelve ['ok' => true, 'tmp_name', 'mime', 'sha1'] si está bien, o
 * ['ok' => false, 'error'] con el motivo en español.
 */
function validate_photo(array $file): array
{
    $tmp = (string) ($file['tmp_name'] ?? '');
    $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    // Errores que devuelve PHP mismo durante la subida.
    if ($uploadError !== UPLOAD_ERR_OK) {
        $message = match ($uploadError) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La foto supera el límite permitido.',
            UPLOAD_ERR_NO_FILE => 'No se envió ninguna foto.',
            default => 'No se pudo subir la foto, probá de nuevo.',
        };

        return ['ok' => false, 'error' => $message];
    }

    if ($tmp === '' || !is_file($tmp)) {
        return ['ok' => false, 'error' => 'No se pudo subir la foto, probá de nuevo.'];
    }

    // Peso: 2 MB inclusive.
    $size = filesize($tmp);
    if ($size === false || $size === 0 || $size > PHOTO_MAX_BYTES) {
        return ['ok' => false, 'error' => 'La foto debe pesar hasta 2 MB inclusive.'];
    }

    // Tipo real: la extensión del nombre no prueba nada, finfo sí.
    $ext = photo_extension($tmp);
    if ($ext === null) {
        return ['ok' => false, 'error' => 'La foto debe ser una imagen JPEG, PNG o WEBP.'];
    }

    // Medidas mínimas.
    $sizeInfo = getimagesize($tmp);
    if ($sizeInfo === false || $sizeInfo[0] < PHOTO_MIN_WIDTH || $sizeInfo[1] < PHOTO_MIN_HEIGHT) {
        return ['ok' => false, 'error' => 'La foto debe medir al menos 200×200 píxeles.'];
    }

    return [
        'ok' => true,
        'tmp_name' => $tmp,
        'mime' => (string) $sizeInfo['mime'],
        'sha1' => sha1_file($tmp),
    ];
}

/**
 * Valida el lote completo de fotos del formulario (RF-3 y RF-6).
 * Respeta el máximo de $max fotos, detecta repetidas por hash dentro del mismo
 * lote y junta los motivos de cada rechazo numerados como "Foto 2: ...".
 * $existingHashes (solo al editar) trae los hashes de las fotos ya guardadas
 * de la publicación: así una foto idéntica a una existente se rechaza como
 * repetida, igual que una repetida dentro del propio lote.
 * Devuelve ['valid' => [entradas], 'errors' => [mensajes]].
 */
function photo_batch(array $files, int $max = 4, array $existingHashes = []): array
{
    // $_FILES con name="photos[]" trae arreglos; con un solo nombre, escalares.
    $tmpNames = is_array($files['tmp_name'] ?? null) ? $files['tmp_name'] : [($files['tmp_name'] ?? '')];
    $uploadErrors = is_array($files['error'] ?? null) ? $files['error'] : [($files['error'] ?? UPLOAD_ERR_NO_FILE)];

    $valid = [];
    $errors = [];
    // Las que ya están guardadas cuentan como vistas desde el principio.
    $seen = array_fill_keys($existingHashes, true);

    foreach ($tmpNames as $i => $tmp) {
        $label = 'Foto ' . ($i + 1) . ': ';
        $uploadError = $uploadErrors[$i] ?? UPLOAD_ERR_NO_FILE;

        // Un campo vacío (el usuario no eligió ese archivo) no es una foto:
        // se saltea sin ruido; si no quedó ninguna válida, el formulario
        // avisa igual que falta al menos una.
        if ($uploadError === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if (count($valid) >= $max) {
            $errors[] = $label . 'máximo ' . $max . ' fotos por publicación.';
            continue;
        }

        $result = validate_photo([
            'tmp_name' => $tmp,
            'error' => $uploadError,
        ]);

        if (!$result['ok']) {
            $errors[] = $label . $result['error'];
            continue;
        }

        if (isset($seen[$result['sha1']])) {
            // Mensaje distinto según contra qué se comparó: el lote en sí o
            // una foto que la publicación ya tiene guardada (edición).
            $errors[] = $label . (in_array($result['sha1'], $existingHashes, true)
                ? 'es repetida: ya está guardada en la publicación.'
                : 'es repetida dentro de la misma publicación.');
            continue;
        }

        $seen[$result['sha1']] = true;
        $valid[] = $result;
    }

    return ['valid' => $valid, 'errors' => $errors];
}

/**
 * Mueve una foto ya validada a public/uploads con un nombre generado por el
 * servidor (aleatorio + extensión real) y devuelve ['ok', 'filename'].
 * Si la operación falla, se borra lo que haya podido quedar.
 */
function save_photo(array $validated, string $dir = ''): array
{
    $dir = $dir !== '' ? $dir : uploads_dir();
    // photo_extension() ya devuelve la extensión a partir del tipo real.
    $ext = photo_extension($validated['tmp_name']);

    if ($ext === null) {
        return ['ok' => false, 'error' => 'La foto debe ser una imagen JPEG, PNG o WEBP.'];
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destination = $dir . '/' . $filename;
    $tmp = $validated['tmp_name'];

    // is_uploaded_file es lo correcto en web; el fallback por rename existe
    // solo para poder probar desde la línea de comandos (tests).
    $moved = is_uploaded_file($tmp) ? move_uploaded_file($tmp, $destination) : rename($tmp, $destination);

    if (!$moved) {
        return ['ok' => false, 'error' => 'No se pudo guardar la foto en el servidor.'];
    }

    return ['ok' => true, 'filename' => $filename];
}

/**
 * Borra una foto del disco. Se usa si el guardado de la publicación falla a
 * mitad de camino, para no dejar archivos huérfanos (y en el borrado de la
 * moto, tarea 4.3). El nombre se recorta a su base para que ningún path raro
 * pueda salirse de la carpeta de uploads.
 */
function delete_photo_file(string $filename, string $dir = ''): void
{
    $dir = $dir !== '' ? $dir : uploads_dir();
    $path = $dir . '/' . basename($filename);

    if (is_file($path)) {
        unlink($path);
    }
}
