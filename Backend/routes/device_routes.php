<?php

use Controllers\DeviceController;

global $relativeUri, $requestMethod, $db;

$controller = new DeviceController($db);

// Crear heladera
if ($relativeUri === '/api/devices' && $requestMethod === 'POST') {
    $controller->create(); exit;
}

// Obtener todas las accesibles al usuario
if ($relativeUri === '/api/devices' && $requestMethod === 'GET') {
    $controller->getAll(); exit;
}

// Obtener una heladera por ID
if (preg_match('#^/api/devices/(\d+)$#', $relativeUri, $matches) && $requestMethod === 'GET') {
    $controller->getOne($matches[1]); exit;
}

// Actualizar heladera
if (preg_match('#^/api/devices/(\d+)$#', $relativeUri, $matches) && $requestMethod === 'PUT') {
    $controller->update($matches[1]); exit;
}

// Eliminar heladera
if (preg_match('#^/api/devices/(\d+)$#', $relativeUri, $matches) && $requestMethod === 'DELETE') {
    $controller->delete($matches[1]); exit;
}

// Otorgar acceso
if (preg_match('#^/api/devices/(\d+)/grant-access$#', $relativeUri, $matches) && $requestMethod === 'POST') {
    $controller->grantAccess($matches[1]); exit;
}

// Revocar acceso
if (preg_match('#^/api/devices/(\d+)/revoke-access$#', $relativeUri, $matches) && $requestMethod === 'POST') {
    $controller->revokeAccess($matches[1]); exit;
}
if ($relativeUri === '/api/devices/assign-to-user' && $requestMethod === 'POST') {
    $controller->assignToUser(); exit;
}
if ($relativeUri === '/api/devices/unassigned' && $requestMethod === 'GET') {
    $controller->getUnassigned(); exit;
}
if (preg_match('#^/api/devices/(\d+)/assign-group$#', $relativeUri, $matches) && $requestMethod === 'POST') {
    $controller->assignGroup($matches[1]); exit;
}

