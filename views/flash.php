<?php
// views/flash.php — muestra y consume los mensajes dejados con flash_set().
// Lo incluye el header de cada página, así un aviso post-redirect (patrón
// PRG) se ve una sola vez y nunca se pierde.

?>
<?php foreach (flash_pull() as $flash): ?>
    <!-- Banner arriba de la página: fondo suave + barra de color según el
         tipo (ver docs/design.md). La clase .error/.notice de campo no se
         toca: abajo de cada input sigue siendo texto rojo/verde. -->
    <p class="flash <?= $flash['type'] === 'error' ? 'flash-error' : 'flash-notice' ?>"><?= htmlspecialchars($flash['message']) ?></p>
<?php endforeach; ?>
