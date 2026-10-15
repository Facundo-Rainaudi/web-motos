<?php
// tests/Unit/CsrfTest.php
// Verifica el token CSRF (defensa de todos los formularios con sesión):
// el token que genera la propia sesión pasa el chequeo, y uno alterado,
// vacío o ausente no.

declare(strict_types=1);

it('acepta el token que genera la propia sesión', function () {
    session_start_secure();

    expect(csrf_check(csrf_token()))->toBeTrue();
});

it('rechaza un token alterado', function () {
    session_start_secure();
    $token = csrf_token();
    $tampered = 'a' . substr($token, 1);

    expect(csrf_check($tampered))->toBeFalse();
});

it('rechaza un token ausente o vacío', function () {
    session_start_secure();

    expect(csrf_check(null))->toBeFalse()
        ->and(csrf_check(''))->toBeFalse();
});

it('mantiene el mismo token mientras viva la sesión', function () {
    session_start_secure();

    expect(csrf_token())->toBe(csrf_token());
});
