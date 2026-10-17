<?php
// public/my-bikes.php — listado "Mis publicaciones" (RF-9, historia H7).
//
// El dueño ve solo SUS motos (la consulta filtra por user_id) con su estado
// (Disponible/Vendida) y las tres acciones de dueño: Editar (tarea 4.2),
// Borrar (4.3) y Marcar como vendida / Volver a disponible (4.4).
// Un visitante que llega directo a la URL es mandado al login (RF-2/RF-9).
// El estado vacío invita a publicar la primera moto.
// Cada acción de las dos últimas es un formulario POST con CSRF contra
// bike_actions.php: aunque alguien manipule el HTML, la verificación de que
// la moto es propia se repite SIEMPRE en el servidor (RF-10).

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';
session_start_secure();
require_login_or_redirect();

$bikes = list_bikes_by_user(db_connect(), (int) current_user_id());

$pageTitle = 'Mis publicaciones — Bike Center';
require __DIR__ . '/../views/header.php';
?>
<section class="card">
    <h1>Mis publicaciones</h1>

    <?php if ($bikes === []): ?>
        <p class="muted">Todavía no publicaste ninguna moto. <a href="/publish.php">Publicá tu primera moto</a>.</p>
    <?php else: ?>
        <div class="my-bikes">
            <?php foreach ($bikes as $bike): ?>
                <?php require __DIR__ . '/../views/bike_card.php'; ?>

                <!-- Acciones de dueño: se ven solo acá, en SU listado. -->
                <div class="owner-actions">
                    <a class="button" href="/edit-bike.php?id=<?= (int) $bike['id'] ?>">Editar</a>

                    <form method="post" action="/bike_actions.php"
                          onsubmit="return confirm('¿Borrar la publicación? También se eliminan sus fotos.');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="bike_id" value="<?= (int) $bike['id'] ?>">
                        <button type="submit" class="button button-danger">Borrar</button>
                    </form>

                    <form method="post" action="/bike_actions.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                        <input type="hidden" name="action" value="sell">
                        <input type="hidden" name="bike_id" value="<?= (int) $bike['id'] ?>">
                        <button type="submit" class="button button-secondary">
                            <?= $bike['status'] === 'sold' ? 'Volver a disponible' : 'Marcar como vendida' ?>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../views/footer.php'; ?>
