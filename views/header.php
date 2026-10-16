<?php
// views/header.php — abre el HTML de cada página y arma la navegación según
// el estado de la sesión (RF-2). Las páginas pueden pasar $pageTitle.
// Toda salida pasa por htmlspecialchars(): los datos de usuario nunca se
// imprimen crudos (constitución, seguridad).

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle ?? 'Bike Center') ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="/index.php">Bike Center</a>
    <nav class="site-nav">
        <?php if (current_user_id() !== null): ?>
            <a href="/publish.php">Publicar</a>
            <form method="post" action="/logout.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                <button type="submit" class="link-button">Cerrar sesión</button>
            </form>
        <?php else: ?>
            <a href="/register.php">Crear cuenta</a>
            <a href="/login.php">Iniciar sesión</a>
        <?php endif; ?>
    </nav>
</header>
<?php require __DIR__ . '/flash.php'; ?>
<main class="site-main">
