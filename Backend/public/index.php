<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Config\Database;
use Middleware\CORSMiddleware;

// ✅ Cargar variables de entorno ANTES de usar $_ENV
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

CORSMiddleware::handle();

$maxBodyBytes = (int)($_ENV['MAX_JSON_BODY_BYTES'] ?? 1048576);
$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > $maxBodyBytes) {
    http_response_code(413);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'status' => 413,
        'message' => 'Payload demasiado grande',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Crear conexión con base de datos
require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();

// Definir ruta base dinámica
$basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
define('BASE_PATH', $basePath === '/' ? '' : $basePath);

// Incluir el enrutador principal
require_once __DIR__ . '/../routes/api.php';
