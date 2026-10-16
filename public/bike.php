<?php
// public/bike.php — detalle de una moto (RF-5, H5).
//
// Con sesión iniciada se ve todo: título, marca, estado, precio con moneda,
// descripción literal, sus fotos en carrusel (una a la vez con botones ◀ ▶),
// el nombre del vendedor y la fecha de publicación. Sin sesión se pide registrarse o iniciar sesión sin filtrar
// ningún dato. Moto inexistente o borrada → mensaje de "no disponible" sin
// datos. Nunca se muestra el teléfono del vendedor (RNF-4).

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
session_start_secure();
require_login_or_redirect();

$bikeId = (int) ($_GET['id'] ?? 0);
$bike = $bikeId > 0 ? find_bike_with_owner(db_connect(), $bikeId) : null;

$pageTitle = $bike === null
    ? 'Moto no disponible — Bike Center'
    : $bike['title'] . ' — Bike Center';
require __DIR__ . '/../views/header.php';
?>
<section class="card bike-detail">
<?php if ($bike === null): ?>
    <h1>Moto no disponible</h1>
    <p class="muted">Esta moto no existe o fue borrada por su dueño.</p>
    <p><a href="/index.php">Volver a la vitrina</a></p>
<?php else: ?>
    <?php
    $photos = list_photos(db_connect(), (int) $bike['id']);
    $sold = $bike['status'] === 'sold';
    ?>
    <h1><?= htmlspecialchars($bike['title']) ?></h1>
    <p class="bike-brand">
        <?= htmlspecialchars($bike['brand']) ?> ·
        <?= $bike['condition'] === 'new' ? 'Nueva' : 'Usada' ?>
    </p>
    <p class="bike-price bike-price-big">
        <?= htmlspecialchars($bike['currency']) ?> <?= htmlspecialchars(format_price($bike['price'])) ?>
    </p>
    <p class="badge <?= $sold ? 'badge-sold' : 'badge-available' ?>"><?= $sold ? 'Vendida' : 'Disponible' ?></p>

    <?php if ($photos !== []): ?>
        <!-- Carrusel de fotos (RF-5): se ve una por vez con botones ◀ ▶ y
             contador. Sin JS el contenedor no recibe la clase .js y quedan
             todas las fotos visibles apiladas: nada se pierde sin JavaScript.
             Los botones solo existen si hay más de una foto. -->
        <div class="gallery" data-total="<?= count($photos) ?>">
            <?php foreach ($photos as $index => $photo): ?>
                <img class="gallery-photo<?= $index === 0 ? ' is-active' : '' ?>"
                     src="/uploads/<?= htmlspecialchars($photo['filename']) ?>"
                     alt="Foto <?= $index + 1 ?> de <?= htmlspecialchars($bike['title']) ?>"
                     <?= $index === 0 ? '' : 'loading="lazy"' ?>>
            <?php endforeach; ?>

            <?php if (count($photos) > 1): ?>
                <button type="button" class="gallery-nav gallery-prev" aria-label="Foto anterior">◀</button>
                <button type="button" class="gallery-nav gallery-next" aria-label="Foto siguiente">▶</button>
                <p class="gallery-status muted" role="status">Foto <span data-gallery-current>1</span> de <?= count($photos) ?></p>
                <script src="/js/gallery.js" defer></script>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <h2>Descripción</h2>
    <!-- htmlspecialchars: si la descripción contiene <script> u otra etiqueta,
         se muestra como texto literal y no se ejecuta (RF-5). -->
    <p class="bike-description"><?= htmlspecialchars($bike['description']) ?></p>

    <dl class="bike-meta">
        <dt>Vendedor</dt>
        <dd><?= htmlspecialchars($bike['seller_name']) ?></dd>
        <dt>Publicada el</dt>
        <dd><?= date('d/m/Y H:i', strtotime($bike['created_at'])) ?></dd>
    </dl>

    <p><a href="/index.php">Volver a la vitrina</a></p>
<?php endif; ?>
</section>
<?php require __DIR__ . '/../views/footer.php';
