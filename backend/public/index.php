<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Config\Database;

// ✅ Cargar variables de entorno ANTES de usar $_ENV
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Crear conexión con base de datos
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

// Definir ruta base dinámica
define('BASE_PATH', str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])));

// Incluir el enrutador principal
require_once __DIR__ . '/../routes/api.php';
