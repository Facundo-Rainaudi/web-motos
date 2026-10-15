<?php
// public/login.php — pantalla de inicio de sesión (RF-2, historia H3).
// El mensaje de error es siempre el mismo, sin decir si falló el correo o la
// contraseña (RF-2).

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
session_start_secure();

// Quien ya tiene sesión no vuelve a entrar.
if (current_user_id() !== null) {
    header('Location: /index.php');
    exit;
}

$error = null;
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null)) {
        $error = 'El formulario expiró o no era válido. Volvé a intentarlo.';
    } else {
        $email = (string) ($_POST['email'] ?? '');
        $oldEmail = $email;

        $result = authenticate_user(db_connect(), $email, (string) ($_POST['password'] ?? ''));

        if ($result['ok']) {
            login_user_id($result['user_id']);
            header('Location: /index.php');
            exit;
        }

        // Mensaje genérico a propósito (RF-2): no se revela cuál dato está mal.
        $error = 'El correo o la contraseña no son correctos.';
    }
}

$pageTitle = 'Iniciar sesión — Bike Center';
require __DIR__ . '/../views/header.php';
?>
<section class="card">
    <h1>Iniciar sesión</h1>

    <?php if ($error !== null): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post" action="/login.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($oldEmail) ?>" required>

        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required>

        <button type="submit" class="button">Entrar</button>
    </form>

    <p class="muted">¿Todavía no tenés cuenta? <a href="/register.php">Creala acá</a></p>
</section>
<?php require __DIR__ . '/../views/footer.php';
