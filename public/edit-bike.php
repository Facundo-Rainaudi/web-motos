<?php
// public/edit-bike.php — edición de una publicación propia (RF-6, H6).
//
// UN SOLO formulario que apuntan a esta misma página y se distingue por el
// botón que se presiona:
// - "Guardar cambios" (save_data): guarda juntos los datos del formulario,
//   las fotos elegidas en los recuadros vacíos y el reemplazo elegido en el
//   recuadro de foto única. Antes eran dos formularios separados y la foto
//   elegida se perdía en silencio si el usuario apretaba "Guardar" (bug
//   reportado 2026-10-19; ver docs/decisions.md).
// - ✕ (remove_photo_id) y "Reemplazar" (replace_photo_id): acciones sobre UNA
//   foto concreta; son botones con name/value dentro del MISMO formulario (no
//   hay formularios anidados) y el servidor las distingue por el nombre.
// Los datos de views/bike_form.php viajan dentro de este form ($inlineForm).
// La verificación de dueño es SIEMPRE en el servidor (RF-10): aunque alguien
// tipee la URL a mano, si la moto no es suya no llega ni a los formularios.
//
// Reglas de fotos (RF-6): nunca menos de una ni más de cuatro, tipo/peso/
// medidas como al publicar, y repetidas contra las ya guardadas por hash.

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
session_start_secure();
require_login_or_redirect();

$db = db_connect();
// El id puede venir por GET (abrir la página) o por POST (los formularios).
$bikeId = (int) ($_GET['id'] ?? 0) > 0 ? (int) $_GET['id'] : (int) ($_POST['bike_id'] ?? 0);
$bike = $bikeId > 0 ? find_bike_with_owner($db, $bikeId) : null;

// Moto inexistente o borrada: se vuelve a Mis publicaciones (RF-6).
if ($bike === null) {
    flash_set('error', 'La publicación no existe o ya fue borrada.');
    header('Location: /my-bikes.php');
    exit;
}

// No es suya: fuera. El dueño real sale de la base, nunca del formulario.
if ((int) $bike['user_id'] !== (int) current_user_id()) {
    flash_set('error', 'Solo el dueño puede editar esta publicación.');
    header('Location: /bike.php?id=' . $bikeId);
    exit;
}

$errors = [];
$old = [
    'title' => $bike['title'],
    'brand' => $bike['brand'],
    'condition' => $bike['condition'],
    'price' => $bike['price'],
    'currency' => $bike['currency'],
    'description' => $bike['description'],
];

/**
 * Hashes sha1 de las fotos ya guardadas de la publicación: sirven para
 * rechazar como repetida cualquier foto igual a una existente (RF-6).
 * $exceptPhotoId permite excluir una foto (la que se reemplaza).
 */
$existingHashes = static function (int $exceptPhotoId = 0) use ($db, $bikeId): array {
    $hashes = [];
    foreach (list_photos($db, $bikeId) as $photo) {
        if ((int) $photo['id'] === $exceptPhotoId) {
            continue;
        }
        $hash = sha1_file(uploads_dir() . '/' . $photo['filename']);
        if ($hash !== false) {
            $hashes[] = $hash;
        }
    }

    return $hashes;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Un solo chequeo de CSRF para las cuatro acciones del formulario.
    if (!csrf_check(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null)) {
        flash_set('error', 'El formulario expiró o no era válido. Volvé a intentarlo.');
        header('Location: /edit-bike.php?id=' . $bikeId);
        exit;
    }

    $photos = list_photos($db, $bikeId);

    // --- Quitar UNA foto (botón ✕ de un recuadros relleno) ---
    if (isset($_POST['remove_photo_id'])) {
        $result = delete_photo_of_bike($db, $bikeId, (int) $_POST['remove_photo_id']);

        if ($result['ok']) {
            // La fila ya no existe: ahora se borra el archivo del disco.
            delete_photo_file($result['filename']);
            flash_set('notice', 'Foto quitada de la publicación.');
        } else {
            flash_set('error', $result['error']);
        }

        header('Location: /edit-bike.php?id=' . $bikeId);
        exit;
    }

    // --- Reemplazar la ÚNICA foto (la que no se puede quitar) ---
    if (isset($_POST['replace_photo_id'])) {
        $result = validate_photo($_FILES['photo'] ?? []);

        if ($result['ok'] && in_array($result['sha1'], $existingHashes((int) $_POST['replace_photo_id']), true)) {
            $result = ['ok' => false, 'error' => 'La foto es repetida: ya está guardada en la publicación.'];
        }

        if (!$result['ok']) {
            flash_set('error', $result['error']);
        } else {
            $saved = save_photo($result);

            if (!$saved['ok']) {
                flash_set('error', $saved['error']);
            } else {
                $replaced = replace_photo_of_bike($db, $bikeId, (int) $_POST['replace_photo_id'], $saved['filename']);

                if ($replaced['ok']) {
                    // La fila ya apunta a la foto nueva: se borra la vieja.
                    delete_photo_file($replaced['filename']);
                    flash_set('notice', 'Foto reemplazada.');
                } else {
                    // No se usó la foto nueva: se borra del disco.
                    delete_photo_file($saved['filename']);
                    flash_set('error', $replaced['error']);
                }
            }
        }

        header('Location: /edit-bike.php?id=' . $bikeId);
        exit;
    }

    // --- Guardar TODO: los datos + las fotos elegidas en los recuadros ---
    // Es el único botón "Guardar cambios": los recuadros vacíos (photos[]) y
    // los campos viajan en el MISMO formulario, así que este guardado procesa
    // ambos de una. (Arreglo del bug reportado 2026-10-19: la foto elegida se
    // perdía en silencio porque los archivos vivían en otro formulario.)
    if (isset($_POST['save_data'])) {
        $raw = [
            'title' => (string) ($_POST['title'] ?? ''),
            'brand' => (string) ($_POST['brand'] ?? ''),
            'condition' => (string) ($_POST['condition'] ?? ''),
            'price' => (string) ($_POST['price'] ?? ''),
            'currency' => (string) ($_POST['currency'] ?? ''),
            'description' => (string) ($_POST['description'] ?? ''),
        ];
        $old = $raw;

        $title = validate_title($raw['title']);
        $brand = validate_brand($raw['brand']);
        $condition = validate_condition($raw['condition']);
        $price = validate_price($raw['price']);
        $currency = validate_currency($raw['currency']);
        $description = validate_description($raw['description']);

        foreach ([
            'title' => $title,
            'brand' => $brand,
            'condition' => $condition,
            'price' => $price,
            'currency' => $currency,
            'description' => $description,
        ] as $field => $result) {
            if (!$result['ok']) {
                $errors[$field] = $result['error'];
            }
        }

        // Los datos no mueven created_at ni status: el feed no cambia de
        // orden y una moto vendida se puede editar (RF-6).
        if ($errors === []) {
            update_bike($db, $bikeId, [
                'title' => $title['value'],
                'brand' => $brand['value'],
                'condition' => $condition['value'],
                'price' => $price['value'],
                'currency' => $currency['value'],
                'description' => $description['value'],
            ]);

            // El banner de resultado se arma en orden: primero la base y
            // después lo que cada bloque (reemplazo / fotos nuevas) agregue.
            $flashType = 'notice';
            $message = 'Cambios guardados.';

            // --- Recuadros de UNA foto: reemplazo elegido sin apretar
            // "Reemplazar" (se aplica al guardar, mismo criterio). ---
            if (count($photos) === 1 && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $swapResult = validate_photo($_FILES['photo']);

                if ($swapResult['ok'] && in_array($swapResult['sha1'], $existingHashes((int) $photos[0]['id']), true)) {
                    $swapResult = ['ok' => false, 'error' => 'La foto es repetida: ya está guardada en la publicación.'];
                }

                if (!$swapResult['ok']) {
                    $flashType = 'error';
                    $message .= ' Foto de reemplazo rechazada: ' . $swapResult['error'];
                } else {
                    $savedSwap = save_photo($swapResult);

                    if (!$savedSwap['ok']) {
                        $flashType = 'error';
                        $message .= ' No se pudo reemplazar la foto: ' . $savedSwap['error'];
                    } else {
                        $replaced = replace_photo_of_bike($db, $bikeId, (int) $photos[0]['id'], $savedSwap['filename']);

                        if ($replaced['ok']) {
                            delete_photo_file($replaced['filename']);
                            $message .= ' Foto reemplazada.';
                            // La grilla cambió: se refresca para el cupo de
                            // las fotos nuevas de abajo.
                            $photos = list_photos($db, $bikeId);
                        } else {
                            delete_photo_file($savedSwap['filename']);
                            $flashType = 'error';
                            $message .= ' No se pudo reemplazar la foto: ' . $replaced['error'];
                        }
                    }
                }
            }

            // --- Fotos nuevas elegidas en los recuadros (si las hay) ---
            // Mismas reglas que al publicar (RF-6): cupo de 4 en total,
            // repetidas contra las ya guardadas por hash y tipo/peso/
            // medidas. Si no se eligió ninguna foto, sólo se guardan datos.
            $batch = photo_batch($_FILES['photos'] ?? [], 4 - count($photos), $existingHashes());

            if ($batch['valid'] !== []) {
                // Se mueven los archivos y se agregan las filas al final de
                // la numeración; si algo falla, se borran los archivos.
                $savedFiles = [];

                foreach ($batch['valid'] as $entry) {
                    $saved = save_photo($entry);

                    if (!$saved['ok']) {
                        break;
                    }

                    $savedFiles[] = $saved['filename'];
                }

                if (count($savedFiles) === count($batch['valid'])) {
                    try {
                        $nextPosition = next_photo_position($db, $bikeId);

                        foreach ($savedFiles as $i => $filename) {
                            insert_photo($db, $bikeId, $filename, $nextPosition + $i);
                        }

                        $message .= ' ' . (count($savedFiles) === 1 ? 'Foto agregada.' : 'Fotos agregadas.');
                    } catch (Throwable $e) {
                        foreach ($savedFiles as $filename) {
                            delete_photo_file($filename);
                        }

                        $flashType = 'error';
                        $message = 'Los datos se guardaron, pero no se pudieron agregar las fotos. Probá de nuevo.';
                    }
                } else {
                    foreach ($savedFiles as $filename) {
                        delete_photo_file($filename);
                    }

                    $flashType = 'error';
                    $message = 'Los datos se guardaron, pero no se pudo guardar la foto en el servidor. Probá de nuevo.';
                }
            }

            // Fotos rechazadas (tipo/peso/medidas/repetida): como al
            // publicar, el motivo viaja por flash junto con el resultado.
            if ($batch['errors'] !== []) {
                $flashType = 'error';
                $message .= ' Fotos rechazadas: ' . implode(' ', $batch['errors']);
            }

            flash_set($flashType, $message);
            header('Location: /bike.php?id=' . $bikeId);
            exit;
        }

        // Con errores de datos se re-renderiza abajo, conservando lo tipeado.
        // Las fotos elegidas en los recuadros se pierden en este caso: un
        // input de archivo no se puede repoblar (igual que al publicar).
    }
}

// El listado de fotos se refresca para la grilla (por si hubo cambios).
$photos = list_photos($db, $bikeId);

$pageTitle = 'Editar publicación — Bike Center';
require __DIR__ . '/../views/header.php';
?>
<section class="card">
    <h1>Editar publicación</h1>
    <p class="muted">Elegí fotos en los recuadros vacíos y corregí los datos: todo se guarda junto con "Guardar cambios". Ningún cambio mueve la moto de lugar en el feed.</p>

    <!-- Un SOLO formulario: la grilla de fotos y los datos viajan juntos,
         así "Guardar cambios" guarda ambos de una (arreglo del bug en que
         la foto elegida se perdía porque vivía en otro formulario). Las
         acciones sobre fotos concretas (✕ y Reemplazar) son botones con
         name/value DENTRO de este mismo form: no hay formularios anidados
         y el servidor distingue cuál se presionó por el nombre. -->
    <form method="post" action="/edit-bike.php?id=<?= $bikeId ?>" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="bike_id" value="<?= $bikeId ?>">

        <h2>Fotos (<?= count($photos) ?> de 4)</h2>
        <!-- Grilla con los 4 recuadros. Los rellenos tienen su control
             (✕ quitar; o campo + botón Reemplazar cuando es la única foto,
             porque no se puede dejar en cero, RF-6) y los vacíos son los
             pickers para agregar fotos nuevas. -->

        <div class="photo-slots">
            <?php foreach ($photos as $photo): ?>
                <div class="photo-slot is-filled">
                    <img class="photo-slot-photo" src="/uploads/<?= htmlspecialchars($photo['filename']) ?>"
                         alt="Foto <?= (int) $photo['position'] + 1 ?> de la publicación">

                    <?php if (count($photos) > 1): ?>
                        <button type="submit" name="remove_photo_id" value="<?= (int) $photo['id'] ?>"
                                class="slot-remove" aria-label="Quitar esta foto">✕</button>
                    <?php else: ?>
                        <!-- Única foto: en vez de quitar, se reemplaza
                             conservando id y posición (RF-6). Con JS basta
                             hacer click en la foto: el recuadros es un
                             <label> del archivo oculto y, al elegirlo,
                             photos.js aprieta el botón "Reemplazar" de
                             abajo (que en este caso queda escondido).
                             Sin JS se ve el archivo y el botón a la vista:
                             el mismo envío, sólo que manual. -->
                        <label class="slot-swap" for="replace-photo">
                            <span class="slot-swap-hint" aria-hidden="true"><br>Cambiar foto</span>
                        </label>
                        <input type="file" id="replace-photo" class="slot-swap-input" name="photo"
                               accept="image/jpeg,image/png,image/webp"
                               aria-label="Elegí la foto de reemplazo">
                        <button type="submit" name="replace_photo_id" value="<?= (int) $photo['id'] ?>"
                                class="button button-secondary slot-swap-button">Reemplazar</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php for ($slot = count($photos); $slot < 4; $slot++): ?>
                <!-- Recuadro vacío: picker de UNA foto (igual en todos los
                     navegadores); photos.js agrega la vista previa y sin
                     JS el recuadro igual selecciona y envía. Su archivo se
                     guarda junto con los datos, con "Guardar cambios". -->
                <label class="photo-slot">
                    <input type="file" class="photo-slot-input" name="photos[]"
                           accept="image/jpeg,image/png,image/webp"
                           aria-label="Foto nueva <?= $slot + 1 ?> de la publicación">
                    <span class="photo-slot-icon" aria-hidden="true">📷</span>
                    <span class="photo-slot-hint">Elegí una foto</span>
                    <img class="photo-slot-preview" alt="">
                </label>
            <?php endfor; ?>
        </div>

        <?php if (count($photos) < 4): ?>
            <script src="/js/photos.js" defer></script>
        <?php endif; ?>

        <h2>Datos</h2>
    <?php if (isset($errors['_form'])): ?>
        <p class="error"><?= htmlspecialchars($errors['_form']) ?></p>
    <?php endif; ?>
    <?php
    $action = '/edit-bike.php?id=' . $bikeId;
    $submitLabel = 'Guardar cambios';
    $submitName = 'save_data';
    // Las fotos se manejan en la grilla de arriba: $photoSlots = 0 evita
    // repetir recuadros y $inlineForm hace que bike_form imprima sólo los
    // campos, dentro de este mismo <form>.
    $photoSlots = 0;
    $inlineForm = true;
    require __DIR__ . '/../views/bike_form.php';
    ?>
    </form>
</section>
<?php require __DIR__ . '/../views/footer.php'; ?>
