<?php
// src/auth.php — registro, login y búsqueda de usuarios (RF-1 y RF-2).
// Recibe siempre el PDO como parámetro: así la lógica es testeable sin
// servidor web y sin globals (constitución, principio 4).

declare(strict_types=1);

/**
 * Busca si ya existe una cuenta con ese correo (el correo se guarda y compara
 * en minúsculas, así que la comparación es case-insensitive por construcción).
 */
function user_exists_by_email(PDO $pdo, string $email): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);

    return $stmt->fetchColumn() !== false;
}

/**
 * Busca un usuario por su ID; devuelve sus datos públicos o null si no existe.
 * No trae el password_hash: ese dato no sale de la base hacia la interfaz.
 */
function find_user_by_id(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare('SELECT id, full_name, email, phone, created_at FROM users WHERE id = :id');
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();

    return $user === false ? null : $user;
}

/**
 * Comprueba que la cuenta exista realmente en la base (RF-2).
 * Lo usa require_login_or_redirect() para descartar sesiones huérfanas: una
 * sesión que apunta a un usuario ya borrado debe volver a pedir login en vez
 * de fallar con un error confuso a mitad de una acción.
 */
function user_exists(PDO $pdo, int $userId): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM users WHERE id = :id');
    $stmt->execute(['id' => $userId]);

    return $stmt->fetchColumn() !== false;
}

/**
 * Valida y crea una cuenta nueva (RF-1).
 * Si algo está mal, devuelve ['ok' => false, 'errors' => [campo => mensaje]]
 * sin tocar la base. Si está todo bien, guarda el usuario y devuelve
 * ['ok' => true, 'user_id' => int]. La contraseña siempre se guarda hasheada
 * con password_hash(); jamás el texto plano.
 */
function register_user(PDO $pdo, string $fullName, string $email, string $password, string $phone): array
{
    $errors = [];

    $nameResult = validate_full_name($fullName);
    if (!$nameResult['ok']) {
        $errors['full_name'] = $nameResult['error'];
    }

    $emailResult = normalize_email($email);
    if (!$emailResult['ok']) {
        $errors['email'] = $emailResult['error'];
    }

    $passwordResult = validate_password($password);
    if (!$passwordResult['ok']) {
        $errors['password'] = $passwordResult['error'];
    }

    $phoneResult = validate_phone($phone);
    if (!$phoneResult['ok']) {
        $errors['phone'] = $phoneResult['error'];
    }

    if ($errors !== []) {
        return ['ok' => false, 'errors' => $errors];
    }

    // El correo ya registrado se informa como motivo (RF-1): no se crea la cuenta.
    if (user_exists_by_email($pdo, $emailResult['value'])) {
        return ['ok' => false, 'errors' => ['email' => 'Ese correo ya está registrado.']];
    }

    $stmt = $pdo->prepare(
        'INSERT INTO users (full_name, email, password_hash, phone)
         VALUES (:full_name, :email, :password_hash, :phone)'
    );
    $stmt->execute([
        'full_name' => $nameResult['value'],
        'email' => $emailResult['value'],
        'password_hash' => password_hash($passwordResult['value'], PASSWORD_DEFAULT),
        'phone' => $phoneResult['value'],
    ]);

    return ['ok' => true, 'user_id' => (int) $pdo->lastInsertId(), 'errors' => []];
}

/**
 * Comprueba correo y contraseña contra la base (RF-2).
 * Devuelve ['ok' => true, 'user_id' => int] o ['ok' => false].
 * Nunca indica cuál de los dos datos está mal: eso lo resuelve la pantalla con
 * un mensaje genérico, para no facilitar el adivinamiento de correos.
 */
function authenticate_user(PDO $pdo, string $email, string $password): array
{
    $emailResult = normalize_email($email);
    if (!$emailResult['ok']) {
        return ['ok' => false];
    }

    $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = :email');
    $stmt->execute(['email' => $emailResult['value']]);
    $user = $stmt->fetch();

    if ($user === false || !password_verify($password, $user['password_hash'])) {
        return ['ok' => false];
    }

    return ['ok' => true, 'user_id' => (int) $user['id']];
}
