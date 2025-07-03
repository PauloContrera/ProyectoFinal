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
require_once __DIR__ . '/fridge_routes.php';
// Aquí luego agregarás otros routers:
// require_once __DIR__ . '/group_routes.php';
// require_once __DIR__ . '/alerts_routes.php';

// Si ningún router hizo exit, responder 404
http_response_code(404);
header('Content-Type: application/json');
echo json_encode(['message' => 'Ruta no encontrada']);
exit;
