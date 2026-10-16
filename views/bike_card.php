<?php
// views/bike_card.php — tarjeta resumida de una moto en la vitrina (RF-4, H1):
// foto principal, título, marca, precio con moneda y etiqueta de estado.
// Recibe $bike (fila del feed, con el campo photo) y siempre imprime con
// htmlspecialchars. public/js/feed.js arma las mismas tarjetas para las motos
// que llegan por scroll infinito.

$sold = $bike['status'] === 'sold';
?>
<article class="bike-card">
    <?php if (!empty($bike['photo'])): ?>
        <!-- Imagen decorativa: el título de abajo ya dice de qué moto se trata,
             por eso alt vacío; loading="lazy" para que cargue de a poco (H10). -->
        <img class="bike-photo" src="/uploads/<?= htmlspecialchars($bike['photo']) ?>"
             alt="" loading="lazy">
    <?php endif; ?>
    <h2><a href="/bike.php?id=<?= (int) $bike['id'] ?>"><?= htmlspecialchars($bike['title']) ?></a></h2>
    <p class="bike-brand"><?= htmlspecialchars($bike['brand']) ?></p>
    <p class="bike-price"><?= htmlspecialchars($bike['currency']) ?> <?= htmlspecialchars(format_price($bike['price'])) ?></p>
    <p class="badge <?= $sold ? 'badge-sold' : 'badge-available' ?>"><?= $sold ? 'Vendida' : 'Disponible' ?></p>
</article>
