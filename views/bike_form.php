<?php
// views/bike_form.php — formulario de motos (RF-3 y RF-6).
// Compartido entre publicar (etapa 2) y editar (tarea 4.2).
// Recibe: $action (a dónde se envía), $errors (errores por campo),
// $old (lo que el usuario había tipeado), $submitLabel (texto del botón),
// $submitName (nombre del botón, opcional: llegan varios botones a la misma
// URL y el servidor los distingue por este nombre),
// $photosOptional (en edición con recuadros propios: la foto nueva es
// opcional), $photoSlots (cuántos recuadros vacíos mostrar: 4 al publicar,
// 0 si las fotos se manejan en su propio bloque) y $inlineForm (true = imprimir
// SÓLO los campos y el botón, sin <form> ni csrf, porque van dentro del
// formulario que ya abrió la página — así edit-bike.php junta fotos y datos
// en UN solo formulario, bug del 2026-10-19).
// Los errores y los valores se imprimen siempre con htmlspecialchars().

$photosOptional = $photosOptional ?? false;
$photoSlots = $photoSlots ?? 4;
$inlineForm = $inlineForm ?? false;
?>
<?php if (!$inlineForm): ?>
<form method="post" action="<?= htmlspecialchars($action) ?>" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
<?php endif; ?>

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

    <?php if ($photoSlots > 0): ?>
        <label><?= $photosOptional
            ? 'Foto(s) nuevas (opcional: JPEG, PNG o WEBP de hasta 2 MB y al menos 200×200)'
            : 'Fotos (1 a 4: JPEG, PNG o WEBP de hasta 2 MB y al menos 200×200)' ?></label>

        <!-- Recuadros: UNO por foto, cada uno con su propio input de archivo.
             Se hace así (y no un solo input con el atributo multiple) porque
             no todos los navegadores dejan elegir varios archivos de una vez
             (Safari no): con un recuadro por foto se comporta igual en todos.
             photos.js agrega la vista previa al elegir; sin JavaScript el
             recuadro igual funciona: el input sigue siendo clickeable y el
             formulario envía la foto elegida. -->
        <div class="photo-slots">
            <?php for ($slot = 0; $slot < $photoSlots; $slot++): ?>
                <label class="photo-slot">
                    <input type="file" class="photo-slot-input" name="photos[]"
                           accept="image/jpeg,image/png,image/webp"
                           aria-label="Foto <?= $slot + 1 ?> de la publicación"
                           <?= (!$photosOptional && $slot === 0) ? 'required' : '' ?>>
                    <span class="photo-slot-icon" aria-hidden="true">📷</span>
                    <span class="photo-slot-hint">Elegí una foto</span>
                    <img class="photo-slot-preview" alt="">
                </label>
            <?php endfor; ?>
        </div>
        <script src="/js/photos.js" defer></script>
    <?php endif; ?>

    <?php if (isset($errors['photos'])): ?>
        <p class="error"><?= htmlspecialchars($errors['photos']) ?></p>
    <?php endif; ?>
    <?php foreach (($errors['photo_details'] ?? []) as $detail): ?>
        <p class="error"><?= htmlspecialchars($detail) ?></p>
    <?php endforeach; ?>

    <button type="submit" class="button"<?php if (isset($submitName)): ?> name="<?= htmlspecialchars($submitName) ?>"<?php endif; ?>><?= htmlspecialchars($submitLabel) ?></button>
<?php if (!$inlineForm): ?>
</form>
<?php endif; ?>
