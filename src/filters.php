<?php
// src/filters.php — parseo puro de los parámetros de búsqueda y filtros de
// la vitrina (spec 02, RF-11, RF-12 y RNF-7).
//
// Recibe el arreglo de la URL (por ejemplo $_GET) y devuelve una lista
// blanca normalizada: cada parámetro se valida POR SEPARADO y el inválido
// queda en null sin tocar a los demás. Así una URL con basura nunca rompe la
// pantalla: se ignora lo que no se entiende y el resto de la selección sigue
// aplicado. Sin BD ni superglobals: es testeable sin servidor (constitución,
// principio 4).

declare(strict_types=1);

/**
 * Devuelve la selección de vitrina armada desde los parámetros de la URL:
 * ['q' => texto, 'condition' => new|used, 'currency' => ARS|USD,
 *  'min' => precio, 'max' => precio, 'status' => 'available']
 * con null en todo lo ausente o inválido.
 */
function parse_feed_filters(array $get): array
{
    $filters = [
        'q' => null,
        'condition' => null,
        'currency' => null,
        'min' => null,
        'max' => null,
        'status' => null,
    ];

    // --- Texto de búsqueda (RF-11): contiene parte del título o de la marca ---
    $q = $get['q'] ?? null;

    // Un parámetro puede llegar como arreglo (?q[]=x): no es un texto, se ignora.
    if (is_string($q)) {
        // Los comodines del LIKE (%, _ y \) no son texto que alguien busque:
        // se quitan acá y así el SQL no necesita cláusula ESCAPE.
        $q = trim(str_replace(['%', '_', '\\'], '', $q));

        // Vacío o solo espacios = sin búsqueda; más de 80 caracteres se
        // recorta (mismo tope que el título de una publicación, RF-3).
        if ($q !== '') {
            $filters['q'] = mb_substr($q, 0, 80);
        }
    }

    // --- Enumerados de la base: lista blanca estricta (RNF-7) ---
    $condition = $get['condition'] ?? null;

    if (is_string($condition) && in_array($condition, ['new', 'used'], true)) {
        $filters['condition'] = $condition;
    }

    $currency = $get['currency'] ?? null;

    if (is_string($currency) && in_array($currency, ['ARS', 'USD'], true)) {
        $filters['currency'] = $currency;
    }

    // --- Precios mínimo y máximo: mismas reglas que al publicar (RF-3) ---
    // Se reutiliza validate_price (acepta coma o punto, hasta 2 decimales);
    // lo que no sea un precio válido se descarta. Un rango invertido
    // (mínimo mayor que máximo) es un error del usuario: se descartan los dos
    // y queda el listado sin filtrar por precio (RF-12).
    $filters['min'] = parse_price_filter($get['min'] ?? null);
    $filters['max'] = parse_price_filter($get['max'] ?? null);

    if ($filters['min'] !== null && $filters['max'] !== null
        && (float) $filters['min'] > (float) $filters['max']) {
        $filters['min'] = null;
        $filters['max'] = null;
    }

    // --- Disponibilidad (RF-12): la única opción con nombre es "solo
    // disponibles"; sin parámetro, se muestran todas como siempre (RF-4). ---
    $status = $get['status'] ?? null;

    if (is_string($status) && $status === 'available') {
        $filters['status'] = 'available';
    }

    return $filters;
}

/**
 * Convierte un parámetro de precio de la URL a su valor normalizado con
 * validate_price (por ejemplo "1.5" o "1,5" → "1.50"); si no es un precio
 * válido, devuelve null (el filtro de ese lado se ignora).
 */
function parse_price_filter(mixed $value): ?string
{
    if (!is_string($value)) {
        return null;
    }

    $result = validate_price($value);

    return $result['ok'] ? $result['value'] : null;
}
