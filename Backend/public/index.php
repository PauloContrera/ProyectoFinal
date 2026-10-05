<?php

/**
 * Front controller de desarrollo (php -S 127.0.0.1:8000 -t Backend/public).
 * La API cuelga de la raiz del servidor, asi que BASE_PATH sale de SCRIPT_NAME.
 * Toda la inicializacion esta en Backend/bootstrap.php, compartida con produccion.
 */

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$basePath = $scriptDir === '/' ? '' : $scriptDir;

require_once __DIR__ . '/../bootstrap.php';
