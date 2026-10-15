<?php
// src/validation.php — reglas puras de datos (RF-1 y RF-3).
// Ninguna función toca la base ni las superglobals: reciben un string y
// devuelven ['ok' => bool, 'value' => mixed, 'error' => string|null].
// Si 'ok' es true, 'value' trae el valor normalizado listo para guardar;
// si es false, 'error' trae el mensaje para mostrarle al usuario (en español).

declare(strict_types=1);

/**
 * Normaliza y valida el correo: sin espacios y en minúsculas, de modo que
 * "Usuario@X.COM" y "usuario@x.com" sean la misma cuenta (RF-1).
 */
function normalize_email(string $email): array
{
    $email = mb_strtolower(trim($email));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'value' => null, 'error' => 'El correo no tiene un formato válido.'];
    }

    return ['ok' => true, 'value' => $email, 'error' => null];
}

/**
 * Valida la contraseña: al menos 8 caracteres y no puede ser solo espacios (RF-1).
 * Se cuentan caracteres, no bytes, y no se recorta: los espacios de adentro son
 * válidos, solo se rechaza una contraseña totalmente vacía.
 */
function validate_password(string $password): array
{
    if (trim($password) === '') {
        return ['ok' => false, 'value' => null, 'error' => 'La contraseña no puede estar vacía.'];
    }

    if (mb_strlen($password) < 8) {
        return ['ok' => false, 'value' => null, 'error' => 'La contraseña debe tener al menos 8 caracteres.'];
    }

    return ['ok' => true, 'value' => $password, 'error' => null];
}

/**
 * Valida el nombre completo: mínimo 3 caracteres una vez recortado (RF-1).
 */
function validate_full_name(string $fullName): array
{
    $fullName = trim($fullName);

    if (mb_strlen($fullName) < 3) {
        return ['ok' => false, 'value' => null, 'error' => 'El nombre completo debe tener al menos 3 caracteres.'];
    }

    return ['ok' => true, 'value' => $fullName, 'error' => null];
}

/**
 * Valida el teléfono: exactamente 10 dígitos; se ignoran espacios y guiones
 * que el usuario haya tipeado, y no se admite "+" ni código de país.
 * Devuelve el formato que guarda la base: "+54 11" seguido de los 10 dígitos,
 * el que usará el contacto por WhatsApp en una spec posterior (RF-1).
 */
function validate_phone(string $phone): array
{
    $digits = str_replace([' ', '-'], '', trim($phone));

    if (preg_match('/^[0-9]{10}$/', $digits) !== 1) {
        return [
            'ok' => false,
            'value' => null,
            'error' => 'El teléfono debe tener exactamente 10 dígitos, sin letras ni signos.',
        ];
    }

    return ['ok' => true, 'value' => '+54 11 ' . $digits, 'error' => null];
}

/**
 * Valida el precio (RF-3): número mayor a cero, con coma o punto como
 * separador decimal y hasta 2 decimales. No se admite el separador de miles,
 * así que "1,500.00" se rechaza y "1500" o "1500,50" pasan.
 * Se devuelve como string con 2 decimales para guardarlo exacto en el DECIMAL.
 */
function validate_price(string $price): array
{
    $price = trim($price);
    $error = 'El precio debe ser un número mayor a cero, con hasta 2 decimales y sin separador de miles.';

    // Formato permitido: dígitos enteros y opcionalmente un separador con 1 o 2 decimales.
    if (preg_match('/^[0-9]+([.,][0-9]{1,2})?$/', $price) !== 1) {
        return ['ok' => false, 'value' => null, 'error' => $error];
    }

    $normalized = str_replace(',', '.', $price);

    if ((float) $normalized <= 0) {
        return ['ok' => false, 'value' => null, 'error' => $error];
    }

    return ['ok' => true, 'value' => number_format((float) $normalized, 2, '.', ''), 'error' => null];
}

/**
 * Valida la marca (RF-3): texto libre obligatorio de hasta 60 caracteres,
 * el mismo límite de la columna brand de la base.
 */
function validate_brand(string $brand): array
{
    $brand = trim($brand);

    if ($brand === '') {
        return ['ok' => false, 'value' => null, 'error' => 'La marca no puede estar vacía.'];
    }

    if (mb_strlen($brand) > 60) {
        return ['ok' => false, 'value' => null, 'error' => 'La marca no puede superar los 60 caracteres.'];
    }

    return ['ok' => true, 'value' => $brand, 'error' => null];
}

/**
 * Valida el título (RF-3): no vacío y hasta 80 caracteres.
 */
function validate_title(string $title): array
{
    $title = trim($title);

    if ($title === '') {
        return ['ok' => false, 'value' => null, 'error' => 'El título no puede estar vacío.'];
    }

    if (mb_strlen($title) > 80) {
        return ['ok' => false, 'value' => null, 'error' => 'El título no puede superar los 80 caracteres.'];
    }

    return ['ok' => true, 'value' => $title, 'error' => null];
}

/**
 * Valida la descripción (RF-3): no vacía y hasta 2000 caracteres.
 */
function validate_description(string $description): array
{
    $description = trim($description);

    if ($description === '') {
        return ['ok' => false, 'value' => null, 'error' => 'La descripción no puede estar vacía.'];
    }

    if (mb_strlen($description) > 2000) {
        return ['ok' => false, 'value' => null, 'error' => 'La descripción no puede superar los 2000 caracteres.'];
    }

    return ['ok' => true, 'value' => $description, 'error' => null];
}

/**
 * Valida la moneda del precio (RNF-6): pesos o dólares, sin conversión.
 */
function validate_currency(string $currency): array
{
    $currency = strtoupper(trim($currency));

    if (!in_array($currency, ['ARS', 'USD'], true)) {
        return ['ok' => false, 'value' => null, 'error' => 'La moneda debe ser pesos (ARS) o dólares (USD).'];
    }

    return ['ok' => true, 'value' => $currency, 'error' => null];
}

/**
 * Valida el estado de la moto (RF-3): nueva o usada.
 */
function validate_condition(string $condition): array
{
    $condition = strtolower(trim($condition));

    if (!in_array($condition, ['new', 'used'], true)) {
        return ['ok' => false, 'value' => null, 'error' => 'El estado debe ser nueva (new) o usada (used).'];
    }

    return ['ok' => true, 'value' => $condition, 'error' => null];
}
