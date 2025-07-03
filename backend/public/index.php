<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Definir ruta base dinámica
define('BASE_PATH', str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])));

// Incluir el enrutador principal
require_once __DIR__ . '/../routes/api.php';
