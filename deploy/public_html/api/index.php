<?php

/**
 * Front controller de produccion. La API se sirve bajo /api, en el mismo dominio
 * que el frontend, y el .htaccess de esta carpeta manda aca todo lo que no sea un
 * archivo real.
 *
 * BASE_PATH queda en '' porque las rutas de routes/ ya incluyen el prefijo /api.
 * Toda la inicializacion esta en bootstrap.php, el mismo archivo que usa el
 * entorno de desarrollo.
 */

$basePath = '';

require_once __DIR__ . '/bootstrap.php';
