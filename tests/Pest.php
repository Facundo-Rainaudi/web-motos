<?php
// tests/Pest.php — bootstrap de la suite de tests.
// Antes de cada tanda se recrea la base bike_center_test desde
// sql/schema.sql (constitución, principio 4): los tests que toquen la base
// siempre empiezan con las tablas vacías y el mismo esquema que producción.

declare(strict_types=1);

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/validation.php';
require_once __DIR__ . '/../src/format.php';
require_once __DIR__ . '/../src/session.php';
require_once __DIR__ . '/../src/csrf.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/storage.php';
require_once __DIR__ . '/../src/models/photo_model.php';
require_once __DIR__ . '/../src/models/bike_model.php';

// Crea bike_center_test si no existe y la deja con el esquema recién cargado.
db_ensure_database('bike_center_test');
$testPdo = db_connect('bike_center_test');
db_execute_schema($testPdo, __DIR__ . '/../sql/schema.sql');

/**
 * Conexión compartida de los tests de integración: siempre a bike_center_test,
 * la base que se recreó recién con este esquema.
 */
function test_db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $pdo = db_connect('bike_center_test');
    }

    return $pdo;
}

// Cada test arranca con las tablas vacías (y el autoincremento en 1).
uses()
    ->beforeEach(function () {
        $pdo = test_db();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['photos', 'bikes', 'users'] as $table) {
            $pdo->exec("TRUNCATE TABLE `$table`");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    })
    ->in(__DIR__);
