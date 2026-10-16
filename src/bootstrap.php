<?php
// src/bootstrap.php — carga todo lo necesario para atender una página web.
// Toda página de public/ empieza con dos líneas: este require y el arranque de
// la sesión. Así el orden de inclusión vive en un solo lugar.

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/format.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/models/photo_model.php';
require_once __DIR__ . '/models/bike_model.php';
