<?php
// public/logout.php — cierra la sesión del usuario (RF-2).
// Acepta solo POST (desde el botón del encabezado, que lleva su token CSRF):
// así un enlace enviado por mail u otra página no puede cerrar la sesión de
// alguien sin que él lo pida.

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
session_start_secure();

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && csrf_check(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null)) {
    logout_user();
}

header('Location: /index.php');
exit;
