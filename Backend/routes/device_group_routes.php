<?php

use Controllers\DeviceGroupController;

global $relativeUri, $requestMethod;
global $db;
$controller = new DeviceGroupController($db);

// Crear grupo
if ($relativeUri === '/api/device-groups' && $requestMethod === 'POST') {
    $controller->create(); exit;
}

// Obtener todos los grupos accesibles
if ($relativeUri === '/api/device-groups' && $requestMethod === 'GET') {
    $controller->getAll(); exit;
}

// Obtener un grupo por ID
if (preg_match('#^/api/device-groups/(\d+)$#', $relativeUri, $matches) && $requestMethod === 'GET') {
    $controller->getOne($matches[1]); exit;
}

// Actualizar grupo
if (preg_match('#^/api/device-groups/(\d+)$#', $relativeUri, $matches) && $requestMethod === 'PUT') {
    $controller->update($matches[1]); exit;
}

// Eliminar grupo
if (preg_match('#^/api/device-groups/(\d+)$#', $relativeUri, $matches) && $requestMethod === 'DELETE') {
    $controller->delete($matches[1]); exit;
}
