<?php
// src/csrf.php — token CSRF para todos los formularios con sesión.
// El token se guarda en la sesión y se imprime como campo oculto en cada
// formulario; al recibir el POST se compara con hash_equals (comparación en
// tiempo fijo, resistente a timing attacks). Un POST sin token válido no
// produce ningún cambio.

declare(strict_types=1);

/**
 * Devuelve el token CSRF de la sesión actual; si no existe, lo genera.
 * Se usa bin2hex(random_bytes(32)) para un token de 64 caracteres imposible
 * de adivinar.
 */
function csrf_token(): string
{
    // Guardia por si la página olvidó arrancar la sesión: mejor un arranque
    // seguro implícito que un error en pantalla.
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start_secure();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Comprueba que el token enviado coincida con el de la sesión.
 * Devuelve false si el token es nulo, vacío o distinto.
 */
function csrf_check(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start_secure();
    }

    $expected = $_SESSION['csrf_token'] ?? '';

    return is_string($token) && $token !== '' && hash_equals($expected, $token);
}
