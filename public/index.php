<?php
// public/index.php — portada del sitio.
// Hoy es un placeholder que confirma quién está conectado; en la etapa 3 se
// convierte en la vitrina pública de motos (RF-4).

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
session_start_secure();

$user = current_user_id() !== null ? find_user_by_id(db_connect(), current_user_id()) : null;

$pageTitle = 'Bike Center';
require __DIR__ . '/../views/header.php';
?>
<section class="card">
    <h1>Bike Center</h1>

    <?php if ($user !== null): ?>
        <p>Hola <strong><?= htmlspecialchars($user['full_name']) ?></strong>, tenés la sesión iniciada.</p>
    <?php else: ?>
        <p>Todavía no estás logueado. Podés <a href="/register.php">crear una cuenta</a>
            o <a href="/login.php">iniciar sesión</a>.</p>
    <?php endif; ?>

    <p class="muted">La vitrina de motos llega en la etapa 3 de la spec 01.</p>
</section>
<?php require __DIR__ . '/../views/footer.php';
