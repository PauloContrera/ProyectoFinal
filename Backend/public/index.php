<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Config\Database;
use Dotenv\Dotenv;
use Helpers\AuditLogger;
use Helpers\Logger;
use Middleware\CORSMiddleware;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

ob_start();

ini_set('display_errors', '0');
error_reporting(E_ALL);

set_exception_handler(function (Throwable $exception): void {
    AuditLogger::recordException($exception);

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
    }

    echo json_encode([
        'success' => false,
        'status' => 500,
        'message' => 'Error interno del servidor',
        'request_id' => AuditLogger::requestId(),
    ], JSON_UNESCAPED_UNICODE);

    AuditLogger::finishRequest();
});

register_shutdown_function(function (): void {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        AuditLogger::recordFatalError($error);
    }

    AuditLogger::finishRequest();
});

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

require_once __DIR__ . '/../config/database.php';
$db = (new Database())->getConnection();
AuditLogger::startRequest($db);

$maxBodyBytes = (int)($_ENV['MAX_JSON_BODY_BYTES'] ?? 1048576);
$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > $maxBodyBytes) {
    Logger::warning('Request body rejected by size limit', [
        'content_length' => $contentLength,
        'max_body_bytes' => $maxBodyBytes,
    ]);

    http_response_code(413);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'status' => 413,
        'message' => 'Payload demasiado grande',
        'request_id' => AuditLogger::requestId(),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

CORSMiddleware::handle();

$basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
define('BASE_PATH', $basePath === '/' ? '' : $basePath);

require_once __DIR__ . '/../routes/api.php';
