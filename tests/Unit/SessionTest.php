<?php
// tests/Unit/SessionTest.php
// Verifica la configuración de la sesión que pide la spec (RF-2): cookie
// httpOnly, SameSite=Lax y SIN fecha de expiración, de modo que la sesión muere
// cuando el usuario cierra la aplicación del navegador.

declare(strict_types=1);

it('configura la cookie de sesión como httpOnly, SameSite=Lax y sin expiración', function () {
    session_start_secure();
    $params = session_get_cookie_params();

    // En PHP 8.5 la clave que expresa "sin expiración" es lifetime = 0
    // (la clave 'expires' no viene cuando no hay vencimiento).
    expect($params['httponly'])->toBeTrue()
        ->and($params['samesite'])->toBe('Lax')
        ->and($params['lifetime'])->toBe(0);
});

it('no arranca dos sesiones a la vez', function () {
    session_start_secure();

    expect(session_status())->toBe(PHP_SESSION_ACTIVE);
});

it('guarda y borra el ID del usuario logueado', function () {
    session_start_secure();

    login_user_id(7);
    expect(current_user_id())->toBe(7);

    logout_user();
    // logout_user() destruye la sesión; en CLI se puede volver a abrir limpia.
    session_start_secure();
    expect(current_user_id())->toBeNull();
});
