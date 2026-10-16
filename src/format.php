<?php
// src/format.php — formato de salida para el usuario.
// Reglas: punto de miles y dos decimales solo cuando el precio tiene fracción
// (2500.00 → "2.500", 2500.50 → "2.500,50"). La vitrina repite estas mismas
// reglas en public/js/feed.js para que las tarjetas cargadas por scroll
// coincidan con las que pinta PHP.

declare(strict_types=1);

/**
 * Formatea un precio para mostrarlo en pantalla (RF-4 y RF-5).
 * Acepta el decimal tal cual viene de la base ("2500.00").
 */
function format_price(string|float|int $price): string
{
    $value = (float) $price;
    $decimals = $value == (int) $value ? 0 : 2;

    return number_format($value, $decimals, ',', '.');
}
