<?php
// public/register.php — pantalla de registro (RF-1, historia H2).
// Patrón PRG: si el registro sale bien se redirige, así que recargar la
// página no reenvía el formulario. Si sale mal, se muestran los errores y se
// conservan los datos ya tipeados (RF-1).

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
session_start_secure();

// Un usuario que ya tiene sesión no necesita registrarse.
if (current_user_id() !== null) {
    header('Location: /index.php');
    exit;
}

$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null)) {
        $errors['_form'] = 'El formulario expiró o no era válido. Volvé a intentarlo.';
    } else {
        $result = register_user(
            db_connect(),
            (string) ($_POST['full_name'] ?? ''),
            (string) ($_POST['email'] ?? ''),
            (string) ($_POST['password'] ?? ''),
            (string) ($_POST['phone'] ?? '')
        );

        if ($result['ok']) {
            // Cuenta creada: queda con sesión iniciada (RF-1) y va a la portada.
            login_user_id($result['user_id']);
            header('Location: /index.php');
            exit;
        }

        $errors = $result['errors'];
        $old = [
            'full_name' => (string) ($_POST['full_name'] ?? ''),
            'email' => (string) ($_POST['email'] ?? ''),
            'phone' => (string) ($_POST['phone'] ?? ''),
        ];
    }
}

$pageTitle = 'Crear cuenta — Bike Center';
require __DIR__ . '/../views/header.php';
?>
<section class="card">
    <h1>Crear cuenta</h1>
    <p class="muted">Registrate para publicar motos y ver el detalle completo de las publicaciones.</p>

    <?php if (isset($errors['_form'])): ?>
        <p class="error"><?= htmlspecialchars($errors['_form']) ?></p>
    <?php endif; ?>

    <form method="post" action="/register.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

        <label for="full_name">Nombre completo</label>
        <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($old['full_name']) ?>" required>
        <?php if (isset($errors['full_name'])): ?>
            <p class="error"><?= htmlspecialchars($errors['full_name']) ?></p>
        <?php endif; ?>

        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email']) ?>" required>
        <?php if (isset($errors['email'])): ?>
            <p class="error"><?= htmlspecialchars($errors['email']) ?></p>
        <?php endif; ?>

        <label for="password">Contraseña (mínimo 8 caracteres)</label>
        <input type="password" id="password" name="password" required>
        <?php if (isset($errors['password'])): ?>
            <p class="error"><?= htmlspecialchars($errors['password']) ?></p>
        <?php endif; ?>

        <label for="phone">Teléfono (10 dígitos, sin espacios ni signos)</label>
        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($old['phone']) ?>" required>
        <?php if (isset($errors['phone'])): ?>
            <p class="error"><?= htmlspecialchars($errors['phone']) ?></p>
        <?php endif; ?>

        <button type="submit" class="button">Crear cuenta</button>
    </form>

    <p class="muted">¿Ya tenés cuenta? <a href="/login.php">Iniciar sesión</a></p>
</section>
<?php require __DIR__ . '/../views/footer.php';
