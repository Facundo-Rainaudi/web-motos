<?php
// views/bike_form.php — formulario de motos (RF-3).
// Compartido entre publicar (etapa 2) y editar (tarea 4.2).
// Recibe: $action (a dónde se envía), $errors (errores por campo),
// $old (lo que el usuario había tipeado) y $submitLabel (texto del botón).
// Los errores y los valores se imprimen siempre con htmlspecialchars().

?>
<form method="post" action="<?= htmlspecialchars($action) ?>" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">

    <label for="title">Título (máximo 80 caracteres)</label>
    <input type="text" id="title" name="title" maxlength="80" value="<?= htmlspecialchars($old['title'] ?? '') ?>" required>
    <?php if (isset($errors['title'])): ?>
        <p class="error"><?= htmlspecialchars($errors['title']) ?></p>
    <?php endif; ?>

    <label for="brand">Marca</label>
    <input type="text" id="brand" name="brand" maxlength="60" value="<?= htmlspecialchars($old['brand'] ?? '') ?>" required>
    <?php if (isset($errors['brand'])): ?>
        <p class="error"><?= htmlspecialchars($errors['brand']) ?></p>
    <?php endif; ?>

    <label for="condition">Estado</label>
    <select id="condition" name="condition" required>
        <option value="">Elegí una opción</option>
        <option value="new" <?= ($old['condition'] ?? '') === 'new' ? 'selected' : '' ?>>Nueva</option>
        <option value="used" <?= ($old['condition'] ?? '') === 'used' ? 'selected' : '' ?>>Usada</option>
    </select>
    <?php if (isset($errors['condition'])): ?>
        <p class="error"><?= htmlspecialchars($errors['condition']) ?></p>
    <?php endif; ?>

    <label for="price">Precio (hasta 2 decimales, sin separador de miles)</label>
    <input type="text" id="price" name="price" value="<?= htmlspecialchars($old['price'] ?? '') ?>" placeholder="Ej.: 2500.50" required>
    <?php if (isset($errors['price'])): ?>
        <p class="error"><?= htmlspecialchars($errors['price']) ?></p>
    <?php endif; ?>

    <label for="currency">Moneda</label>
    <select id="currency" name="currency" required>
        <option value="">Elegí una opción</option>
        <option value="ARS" <?= ($old['currency'] ?? '') === 'ARS' ? 'selected' : '' ?>>Pesos (ARS)</option>
        <option value="USD" <?= ($old['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>Dólares (USD)</option>
    </select>
    <?php if (isset($errors['currency'])): ?>
        <p class="error"><?= htmlspecialchars($errors['currency']) ?></p>
    <?php endif; ?>

    <label for="description">Descripción (máximo 2000 caracteres)</label>
    <textarea id="description" name="description" rows="5" maxlength="2000" required><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
    <?php if (isset($errors['description'])): ?>
        <p class="error"><?= htmlspecialchars($errors['description']) ?></p>
    <?php endif; ?>

    <label for="photos">Fotos (1 a 4: JPEG, PNG o WEBP de hasta 2 MB y al menos 200×200)</label>
    <input type="file" id="photos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required>
    <?php if (isset($errors['photos'])): ?>
        <p class="error"><?= htmlspecialchars($errors['photos']) ?></p>
    <?php endif; ?>
    <?php foreach (($errors['photo_details'] ?? []) as $detail): ?>
        <p class="error"><?= htmlspecialchars($detail) ?></p>
    <?php endforeach; ?>

    <button type="submit" class="button"><?= htmlspecialchars($submitLabel) ?></button>
</form>
