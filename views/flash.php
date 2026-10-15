<?php
// views/flash.php — muestra y consume los mensajes dejados con flash_set().
// Lo incluye el header de cada página, así un aviso post-redirect (patrón
// PRG) se ve una sola vez y nunca se pierde.

?>
<?php foreach (flash_pull() as $flash): ?>
    <p class="<?= $flash['type'] === 'error' ? 'error' : 'notice' ?>"><?= htmlspecialchars($flash['message']) ?></p>
<?php endforeach; ?>
