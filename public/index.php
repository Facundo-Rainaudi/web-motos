<?php
// public/index.php — vitrina pública (RF-4, H1): primeras 20 motos ordenadas
// por creación (más reciente primero) y el resto entra con scroll infinito
// desde js/feed.js contra api/bikes.php (RNF-3). Cualquiera, con o sin
// sesión, puede verla.

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
session_start_secure();

// El listado de la vitrina: sin parámetros en la URL, el listado completo
// del RF-4. Los filtros de búsqueda (spec 02) se conectan en la etapa 1.
$bikes = list_bikes_feed(db_connect(), [], 20, null);
$lastId = $bikes === [] ? null : (int) $bikes[count($bikes) - 1]['id'];

$pageTitle = 'Bike Center — Motos en venta';
require __DIR__ . '/../views/header.php';
?>
<section>
    <h1>Motos en venta</h1>

    <?php if ($bikes === []): ?>
        <!-- Estado vacío: invita a publicar la primera (RF-4). -->
        <p class="empty-state">
            Todavía no hay motos publicadas.
            <?php if (current_user_id() !== null): ?>
                <a href="/publish.php">Publicá la primera</a>.
            <?php else: ?>
                <a href="/register.php">Creá tu cuenta</a> y publicá la primera.
            <?php endif; ?>
        </p>
    <?php else: ?>
        <div id="bike-list"
             data-last-id="<?= $lastId ?? '' ?>"
             data-has-more="<?= count($bikes) === 20 ? '1' : '0' ?>">
            <?php foreach ($bikes as $bike): ?>
                <?php require __DIR__ . '/../views/bike_card.php'; ?>
            <?php endforeach; ?>
        </div>

        <!-- El centinela marca el final de la lista: cuando se ve en pantalla,
             feed.js pide la siguiente tanda al endpoint JSON. -->
        <div id="feed-sentinel" aria-hidden="true"></div>
        <p id="feed-status" class="muted" role="status"></p>
        <script src="/js/feed.js" defer></script>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../views/footer.php';
