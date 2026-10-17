<?php
// public/bike_actions.php — acciones de dueño sobre una publicación
// (RF-7 borrar, RF-8 vender, RF-10 permisos).
//
// Solo acepta POST (llegar por GET vuelve a Mis publicaciones sin tocar nada)
// y siempre redirige al listado con un aviso por flash (patrón PRG).
// El orden de los controles importa:
// 1) sesión iniciada,
// 2) token CSRF válido,
// 3) la acción pedida,
// y recién ahí la verificación de dueño, que SIEMPRE se repite en el
// servidor dentro del modelo: aunque alguien mande el POST a mano con el
// bike_id de otra persona, no logra ningún cambio (RF-10).

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
session_start_secure();
require_login_or_redirect();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /my-bikes.php');
    exit;
}

if (!csrf_check(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null)) {
    flash_set('error', 'El formulario expiró o no era válido. Volvé a intentarlo.');
    header('Location: /my-bikes.php');
    exit;
}

$bikeId = (int) ($_POST['bike_id'] ?? 0);
$action = (string) ($_POST['action'] ?? '');
$actorId = (int) current_user_id();

$result = match ($action) {
    // Borrar: modelo contra modelo, cascada en la base y archivos del disco.
    'delete' => delete_bike_as_owner(db_connect(), $bikeId, $actorId),
    // Vender: alterna disponible ↔ vendida (RF-8, reversible).
    'sell' => toggle_bike_status_as_owner(db_connect(), $bikeId, $actorId),
    default => ['ok' => false, 'error' => 'Acción no reconocida.'],
};

if ($result['ok']) {
    flash_set('notice', match ($action) {
        'delete' => 'Publicación borrada.',
        'sell' => $result['status'] === 'sold' ? 'Moto marcada como vendida.' : 'Moto vuelta a disponible.',
        default => 'Listo.',
    });
} else {
    flash_set('error', $result['error']);
}

header('Location: /my-bikes.php');
exit;
