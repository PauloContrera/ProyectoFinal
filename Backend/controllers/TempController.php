<?php

namespace Controllers;

use Helpers\Response;
use Middleware\AuthMiddleware;
use Models\Device;
use Models\Temperature;

class TempController
{
    private $db;
    private Temperature $temperatureModel;
    private Device $deviceModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->temperatureModel = new Temperature($db);
        $this->deviceModel = new Device($db);
    }

    public function getByDevice(int $deviceId)
    {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];

        $device = $this->deviceModel->getById($deviceId);
        if (!$device) {
            return Response::json(404, 'FRIDGE_NOT_FOUND');
        }

        $isOwner = (int)$device['user_id'] === (int)$user['id'];
        $isAdmin = in_array($user['role'], ['admin', 'superadmin']);
        $hasAccess = $this->deviceModel->getAccess($deviceId, $user['id']);

        if (!$isOwner && !$isAdmin && !$hasAccess) {
            return Response::json(403, 'ACCESS_DENIED');
        }

        $limit = min(200, max(1, (int)($_GET['limit'] ?? 96)));
        return Response::json(200, 'TEMPERATURE_LIST', $this->temperatureModel->getByDeviceId($deviceId, $limit));
    }
}
