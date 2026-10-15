<?php
// public/publish.php — formulario para publicar una moto (RF-3, historia H4).
//
// Orden del guardado (importa):
// 1) Se validan los datos del formulario. Si algo falla, NO se toca ningún
//    archivo: "datos inválidos => no se guarda nada".
// 2) Recién ahí se validan las fotos (tipo real, peso, medidas, máximo 4,
//    repetidas). Si no queda ninguna válida, tampoco se guarda nada.
// 3) Se mueven las fotos válidas al disco y se crea la moto con sus fotos en
//    una transacción. Si la transacción falla, se borran las fotos que se
//    hubieran movido: no queda nada a medias.

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
session_start_secure();
require_login_or_redirect();

$errors = [];
$old = [
    'title' => '',
    'brand' => '',
    'condition' => '',
    'price' => '',
    'currency' => '',
    'description' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null)) {
        $errors['_form'] = 'El formulario expiró o no era válido. Volvé a intentarlo.';
    } else {
        // Paso 1: datos.
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

        // Paso 2: fotos, solo si los datos están bien.
        if ($errors === []) {
            $batch = photo_batch($_FILES['photos'] ?? []);

            if ($batch['valid'] === []) {
                $errors['photos'] = 'La moto necesita al menos una foto válida.';
                $errors['photo_details'] = $batch['errors'];
            } else {
                // Paso 3: mover archivos y guardar en una transacción.
                $savedFiles = [];

                foreach ($batch['valid'] as $entry) {
                    $saved = save_photo($entry);

                    if (!$saved['ok']) {
                        $errors['photos'] = $saved['error'];
                        break;
                    }

                    $savedFiles[] = $saved['filename'];
                }

                if ($errors === []) {
                    try {
                        create_bike(db_connect(), (int) current_user_id(), [
                            'title' => $title['value'],
                            'brand' => $brand['value'],
                            'condition' => $condition['value'],
                            'price' => $price['value'],
                            'currency' => $currency['value'],
                            'description' => $description['value'],
                        ], array_map(static fn (string $filename): array => ['filename' => $filename], $savedFiles));

                        // Publicada. Si hubo fotos rechazadas, el motivo viaja
                        // por flash y se ve en la portada tras el redirect.
                        if ($batch['errors'] !== []) {
                            flash_set(
                                'notice',
                                'Publicación creada con las fotos válidas. Fotos rechazadas: ' . implode(' ', $batch['errors'])
                            );
                        } else {
                            flash_set('notice', '¡Publicación creada! Ya aparece en la vitrina.');
                        }

                        // Mientras no exista la vitrina (etapa 3), se vuelve a
                        // la portada desde donde se confirma la sesión.
                        header('Location: /index.php');
                        exit;
                    } catch (Throwable $e) {
                        // Se deshace lo del disco: la transacción ya deshizo la base.
                        foreach ($savedFiles as $filename) {
                            delete_photo_file($filename);
                        }

                        $errors['_form'] = 'No se pudo guardar la publicación. Ninguna foto ni dato quedó guardado, probá de nuevo.';
                    }
                } else {
                    // Los datos o una foto fallaron después de mover archivos:
                    // no se guardó la moto, así que se borran para no dejar nada.
                    foreach ($savedFiles as $filename) {
                        delete_photo_file($filename);
                    }
                }
            }
        }
    }
}

$pageTitle = 'Publicar moto — Bike Center';
require __DIR__ . '/../views/header.php';
?>
<section class="card">
    <h1>Publicar una moto</h1>
    <p class="muted">Completá los datos y subí entre 1 y 4 fotos. Cualquier visitante va a ver el resumen en la vitrina; el detalle completo, los usuarios registrados.</p>

    <?php if (isset($errors['_form'])): ?>
        <p class="error"><?= htmlspecialchars($errors['_form']) ?></p>
    <?php endif; ?>

    <?php
    $action = '/publish.php';
    $submitLabel = 'Publicar moto';
    require __DIR__ . '/../views/bike_form.php';
    ?>
</section>
<?php require __DIR__ . '/../views/footer.php';
