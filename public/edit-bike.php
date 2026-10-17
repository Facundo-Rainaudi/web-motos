<?php
// public/edit-bike.php — edición de una publicación propia (RF-6, H6).
//
// Dos formularios que apuntan a esta misma página y se distinguen por el
// botón que se presiona:
// 1) FOTOS: UNA grilla con los 4 recuadros. Los que ya tienen foto se ven
//    rellenos con su control (✕ para quitar, o campo + "Reemplazar" cuando
//    es la única); los vacíos son los pickers para agregar fotos nuevas.
// 2) DATOS: reutiliza views/bike_form.php con los MISMOS validadores que al
//    publicar (RF-6), sin la sección de fotos ($photoSlots = 0).
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

    // --- Guardar los datos (botón "Guardar cambios" del formulario de datos) ---
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

            flash_set('notice', 'Cambios guardados.');
            header('Location: /bike.php?id=' . $bikeId);
            exit;
        }

        // Con errores se re-renderiza abajo, conservando lo tipeado.
    } else {
        // --- Agregar fotos nuevas a los recuadros vacíos ---
        $batch = photo_batch($_FILES['photos'] ?? [], 4 - count($photos), $existingHashes());

        if ($batch['valid'] === [] && $batch['errors'] === []) {
            flash_set('error', 'Elegí al menos una foto para agregar.');
        } elseif ($batch['errors'] !== []) {
            flash_set('error', 'No se pudieron agregar las fotos: ' . implode(' ', $batch['errors']));
        } else {
            // Se mueven los archivos y se agregan las filas al final de la
            // numeración; si algo falla, se borran los archivos movidos.
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

                    flash_set('notice', count($savedFiles) === 1 ? 'Foto agregada.' : 'Fotos agregadas.');
                } catch (Throwable $e) {
                    foreach ($savedFiles as $filename) {
                        delete_photo_file($filename);
                    }

                    flash_set('error', 'No se pudieron agregar las fotos. Probá de nuevo.');
                }
            } else {
                foreach ($savedFiles as $filename) {
                    delete_photo_file($filename);
                }

                flash_set('error', 'No se pudo guardar la foto en el servidor. Probá de nuevo.');
            }
        }

        header('Location: /edit-bike.php?id=' . $bikeId);
        exit;
    }
}

// El listado de fotos se refresca para la grilla (por si hubo cambios).
$photos = list_photos($db, $bikeId);

$pageTitle = 'Editar publicación — Bike Center';
require __DIR__ . '/../views/header.php';
?>
<section class="card">
    <h1>Editar publicación</h1>
    <p class="muted">Los cambios de fotos se aplican al tocar su botón; los de datos, con "Guardar cambios". Ninguno mueve la moto de lugar en el feed.</p>

    <h2>Fotos (<?= count($photos) ?> de 4)</h2>
    <!-- Formulario de fotos: una grilla con los 4 recuadros. Los rellenos
         tienen su control (✕ quitar; o campo + botón Reemplazar cuando es
         la única foto, porque no se puede dejar en cero, RF-6) y los vacíos
         son los pickers para agregar. Cada acción es UN botón con nombre:
         el servidor distingue cuál se presionó. -->
    <form method="post" action="/edit-bike.php?id=<?= $bikeId ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="hidden" name="bike_id" value="<?= $bikeId ?>">

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
                     JS el recuadro igual selecciona y envía. -->
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
    </form>

    <h2>Datos</h2>
    <?php if (isset($errors['_form'])): ?>
        <p class="error"><?= htmlspecialchars($errors['_form']) ?></p>
    <?php endif; ?>
    <?php
    $action = '/edit-bike.php?id=' . $bikeId;
    $submitLabel = 'Guardar cambios';
    $submitName = 'save_data';
    // Las fotos se manejan en su propio formulario de arriba.
    $photoSlots = 0;
    require __DIR__ . '/../views/bike_form.php';
    ?>
</section>
<?php require __DIR__ . '/../views/footer.php'; ?>
