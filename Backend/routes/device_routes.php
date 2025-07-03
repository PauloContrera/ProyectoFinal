<?php
use Controllers\DeviceController;
use Middleware\AuthMiddleware;
use Config\Database;

global $relativeUri, $requestMethod;

// Asegurate de proteger todos los endpoints
AuthMiddleware::verifyToken();

$controller = new DeviceController((new Database())->getConnection());
if ($relativeUri === '/api/devices' && $requestMethod === 'POST') {
    (new DeviceController())->create();
    exit;
}

if ($relativeUri === '/api/devices' && $requestMethod === 'GET') {
    (new DeviceController())->getAll();
    exit;
}

if (preg_match('#^/api/devices/(\d+)$#', $relativeUri, $matches)) {
    $id = $matches[1];
    if ($requestMethod === 'GET') {
        (new DeviceController())->getById($id);
        exit;
    }
    if ($requestMethod === 'PUT') {
        (new DeviceController())->update($id);
        exit;
    }
    if ($requestMethod === 'DELETE') {
        (new DeviceController())->delete($id);
        exit;
    }
}

if (preg_match('#^/api/devices/(\d+)/grant-access$#', $relativeUri, $matches) && $requestMethod === 'POST') {
    $id = $matches[1];
    (new DeviceController())->grantAccess($id);
    exit;
}

if (preg_match('#^/api/devices/(\d+)/revoke-access$#', $relativeUri, $matches) && $requestMethod === 'POST') {
    $id = $matches[1];
    (new DeviceController())->revokeAccess($id);
    exit;
}
