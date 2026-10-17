<?php
// src/session.php — arranque y manejo de la sesión del usuario (RF-2).

declare(strict_types=1);

/**
 * Arranca la sesión con la cookie en las condiciones que pide la spec:
 * - httpOnly: el JavaScript de la página no puede leer la cookie.
 * - SameSite=Lax: la cookie no viaja en formularios de otros sitios.
 * - lifetime 0 (sin expiración): la cookie muere cuando el usuario cierra la
 *   aplicación del navegador, que es lo que exige el RF-2.
 * secure queda en false porque en local se corre por http; en producción con
 * https habría que ponerlo en true.
 */
function session_start_secure(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => false,
    ]);

    session_start();
}

/**
 * ID del usuario con sesión iniciada, o null si es un visitante (RF-2).
 */
function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/**
 * Marca al usuario como logueado guardando su ID en la sesión (RF-2).
 */
function login_user_id(int $userId): void
{
    $_SESSION['user_id'] = $userId;
}

/**
 * Cierra la sesión: borra los datos, la cookie y la sesión misma (RF-2).
 */
function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

/**
 * Protege una pantalla que solo tiene sentido con sesión iniciada (RF-2 y RF-9):
 * un visitante que llega directo a la URL se manda al login, donde también
 * puede elegir crear cuenta. Siempre sale del script desde acá.
 * Además corta las sesiones huérfanas: si el usuario de la sesión ya no
 * existe en la base (por ejemplo, se restauró la base de desarrollo), se
 * cierra la sesión y se vuelve a pedir login, en vez de fallar después con
 * un error confuso (como un INSERT con una clave foránea inexistente).
 */
function require_login_or_redirect(string $destination = '/login.php'): void
{
    // Pantallas privadas sin caché del navegador: sin este encabezado, el
    // botón "atrás" después de cerrar sesión puede restaurar la pantalla
    // desde la caché y mostrar datos de una sesión que ya no existe. Con
    // no-store el navegador la vuelve a pedir al servidor y, sin sesión,
    // cae en los guards de abajo y termina en el login (etapa 5, RNF-3).
    header('Cache-Control: no-store');

    $userId = current_user_id();

    if ($userId === null) {
        header('Location: ' . $destination);
        exit;
    }

    if (!user_exists(db_connect(), $userId)) {
        logout_user();
        header('Location: ' . $destination);
        exit;
    }
}

/**
 * Guarda un mensaje para mostrarlo en la PRÓXIMA página que se abra (RF-3).
 * Como las acciones terminan en redirect (patrón PRG), el aviso se manda por
 * la sesión y se consume una sola vez, en views/flash.php.
 */
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Devuelve los mensajes pendientes y los borra: cada aviso se ve una sola vez.
 */
function flash_pull(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $messages;
}
