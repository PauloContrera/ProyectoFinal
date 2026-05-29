<?php

// Extraer URI y método
$requestUri = $_SERVER['REQUEST_URI'];
$uriPath = parse_url($requestUri, PHP_URL_PATH);
$relativeUri = substr($uriPath, strlen(BASE_PATH));
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Hacer disponibles las variables a los routers
global $relativeUri, $requestMethod;

// Incluir routers de cada módulo
require_once __DIR__ . '/user_routes.php';
require_once __DIR__ . '/device_routes.php';
require_once __DIR__ . '/device_group_routes.php';

if ($relativeUri === '/api/esp' && $requestMethod === 'POST') {
    (new \Controllers\EspProtocolController($db))->handle();
    exit;
}

if ($relativeUri === '/api/esp/time' && $requestMethod === 'GET') {
    (new \Controllers\EspProtocolController($db))->time();
    exit;
}

if ($relativeUri === '/api/esp/register' && $requestMethod === 'POST') {
    (new \Controllers\EspProtocolController($db))->register();
    exit;
}

if ($relativeUri === '/api/esp/sync' && $requestMethod === 'POST') {
    (new \Controllers\EspProtocolController($db))->sync();
    exit;
}

if ($relativeUri === '/api/esp/command-response' && $requestMethod === 'POST') {
    (new \Controllers\EspProtocolController($db))->commandResponse();
    exit;
}

if (preg_match('#^/api/devices/(\d+)/temperatures$#', $relativeUri, $matches) && $requestMethod === 'GET') {
    (new \Controllers\TempController($db))->getByDevice((int)$matches[1]);
    exit;
}

if (preg_match('#^/api/devices/(\d+)/stock$#', $relativeUri, $matches) && $requestMethod === 'GET') {
    (new \Controllers\StockController($db))->getByDevice((int)$matches[1]);
    exit;
}

if (preg_match('#^/api/devices/(\d+)/stock$#', $relativeUri, $matches) && $requestMethod === 'POST') {
    (new \Controllers\StockController($db))->create((int)$matches[1]);
    exit;
}

if (preg_match('#^/api/stock/(\d+)$#', $relativeUri, $matches) && $requestMethod === 'PUT') {
    (new \Controllers\StockController($db))->update((int)$matches[1]);
    exit;
}

if (preg_match('#^/api/stock/(\d+)$#', $relativeUri, $matches) && $requestMethod === 'DELETE') {
    (new \Controllers\StockController($db))->delete((int)$matches[1]);
    exit;
}

http_response_code(404);
header('Content-Type: application/json');
echo json_encode(['message' => 'Ruta no encontrada']);
exit;
