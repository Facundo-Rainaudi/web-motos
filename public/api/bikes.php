<?php
// public/api/bikes.php — salida JSON del scroll infinito (RNF-3, H10).
//
// GET /api/bikes.php[?before=<id>] devuelve hasta 20 motos más viejas que el
// cursor y un flag `has_more` para que el navegador sepa si pide otra tanda.
// No requiere sesión: la vitrina es pública. El paginado por clave (id menor
// al último de la tanda) evita repetir o perder motos aunque se publiquen
// nuevas mientras se baja.

declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$before = isset($_GET['before']) ? (int) $_GET['before'] : null;

try {
    // Se pide una fila de más: si llega 21 es que aún queda listado.
    // Los filtros de la selección activa se conectan en la etapa 2 (spec 02):
    // por ahora la tanda sigue siendo el listado completo del RF-4.
    $rows = list_bikes_feed(db_connect(), [], 21, $before);
    $hasMore = count($rows) > 20;
    $rows = array_slice($rows, 0, 20);

    $bikes = array_map(static fn (array $bike): array => [
        'id' => (int) $bike['id'],
        'title' => $bike['title'],
        'brand' => $bike['brand'],
        'price' => $bike['price'],
        'currency' => $bike['currency'],
        'status' => $bike['status'],
        'photo' => $bike['photo'], // nombre de archivo o null si no hay fotos
        'url' => '/bike.php?id=' . (int) $bike['id'],
    ], $rows);

    echo json_encode(['bikes' => $bikes, 'has_more' => $hasMore], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    // El navegador muestra el mensaje de error; el detalle se queda en el log.
    error_log('api/bikes.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['bikes' => [], 'has_more' => false, 'error' => 'No se pudo cargar el listado.'], JSON_UNESCAPED_UNICODE);
}
