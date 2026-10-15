<?php
// src/db.php — conexión a MySQL con PDO y utilidades del esquema.
// Todo el SQL del proyecto vive en src/ (models y estos helpers); las vistas
// nunca tocan la base (constitución, principio 3).

declare(strict_types=1);

/**
 * Lee y cachea la configuración de src/config.php (que devuelve un array).
 * Se cachea en una variable estática para no releer el archivo en cada llamada.
 */
function db_config(): array
{
    static $config = null;

    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }

    return $config;
}

/**
 * Abre una conexión PDO a la base indicada (por defecto la de desarrollo).
 * utf8mb4 para soportar tildes y emojis, errores como excepciones (si algo
 * falla, falla fuerte y no sigue a medias) y sin emular prepares para que las
 * consultas preparadas sean reales del lado de MySQL.
 */
function db_connect(?string $database = null): PDO
{
    $config = db_config();
    $dbname = $database ?? $config['dbname'];

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $config['host'],
        $config['port'],
        $dbname
    );

    return new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

/**
 * Crea la base de datos si no existe (la usan los tests con bike_center_test).
 * Se conecta sin dbname porque, si la base no existe, la conexión fallaría.
 * El nombre se valida contra una lista de caracteres permitidos: aunque hoy no
 * viene de usuario, no se concatena nada que no esté comprobado.
 */
function db_ensure_database(string $name): void
{
    if (preg_match('/^[a-z0-9_]+$/', $name) !== 1) {
        throw new InvalidArgumentException('Nombre de base inválido: ' . $name);
    }

    $config = db_config();

    $dsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $config['host'], $config['port']);
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $pdo->exec(
        "CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    );
}

/**
 * Ejecuta un archivo de esquema SQL sobre la base conectada.
 * PDO no corre varios SQL de una, así que el archivo se parte por ";" y se
 * ejecuta sentencia por sentencia. Ojo: asume que no hay ";" dentro de comillas
 * (nuestro schema.sql no los tiene).
 */
function db_execute_schema(PDO $pdo, string $schemaFile): void
{
    $sql = file_get_contents($schemaFile);

    if ($sql === false) {
        throw new RuntimeException('No se pudo leer el esquema: ' . $schemaFile);
    }

    // Se descartan las líneas de comentario (-- ...) para que no rompan el corte.
    $lines = explode("\n", $sql);
    $lines = array_filter($lines, static fn (string $line): bool => !str_starts_with(trim($line), '--'));
    $sql = implode("\n", $lines);

    foreach (explode(';', $sql) as $statement) {
        $statement = trim($statement);

        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
}
