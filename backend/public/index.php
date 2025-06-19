<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

require_once __DIR__ . '/../routes/api.php';

// Definimos la ruta base dinámica
define('BASE_PATH', str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])));

$requestUri = $_SERVER['REQUEST_URI'];

// Sacamos la parte base para obtener la ruta relativa
$relativeUri = substr($requestUri, strlen(BASE_PATH));

$requestMethod = $_SERVER['REQUEST_METHOD'];

// Rutas con la URI relativa sin carpeta base
if ($relativeUri === '/api/register' && $requestMethod === 'POST') {
    routeRegister();
    exit;
}

if ($relativeUri === '/api/login' && $requestMethod === 'POST') {
    routeLogin();
    exit;
}


if ($relativeUri === '/api/users' && $requestMethod === 'GET') {
    routeGetUsers();
    exit;
}

if (preg_match('#^/api/users/(\d+)$#', $relativeUri, $matches)) {
    $userId = $matches[1];

    if ($requestMethod === 'GET') {
        routeGetUserById($userId);
        exit;
    }

    if ($requestMethod === 'PUT') {
        routeUpdateUser($userId);
        exit;
    }

    if ($requestMethod === 'DELETE') {
        routeDeleteUser($userId);
        exit;
    }
}
if (preg_match('#^/api/users/(\d+)/change-password$#', $relativeUri, $matches) && $requestMethod === 'PUT') {
    $userId = $matches[1];
    routeChangePassword($userId);
    exit;
}

if (preg_match('#^/api/users/(\d+)/change-username$#', $relativeUri, $matches) && $requestMethod === 'PUT') {
    $userId = $matches[1];
    routeChangeUsername($userId);
    exit;
}
if ($relativeUri === '/api/test' && $requestMethod === 'GET') {
    Test();
    exit;
}

// Si no matchea ninguna ruta, responder 404
http_response_code(404);
header('Content-Type: application/json');
echo json_encode(['message' => 'Ruta no encontrada']);
exit;
