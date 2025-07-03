<?php
namespace Controllers;

use Models\Device;
use Middleware\AuthMiddleware;
use Config\Database;
use Helpers\Response;

class DeviceController
{
    private $db;
    private $deviceModel;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->deviceModel = new Device($this->db);
    }

    public function create()
    {
        AuthMiddleware::verifyToken();
        $currentUser = AuthMiddleware::getCurrentUser();
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name'])) {
            return Response::json(400, 'MISSING_NAME');
        }

        $deviceId = $this->deviceModel->create([
            'name' => $data['name'],
            'location' => $data['location'] ?? null,
            'user_id' => $currentUser['id'],
            'group_id' => $data['group_id'] ?? null,
            'min_temp' => $data['min_temp'] ?? 0,
            'max_temp' => $data['max_temp'] ?? 10,
            'firmware_version' => $data['firmware_version'] ?? null
        ]);

        return Response::json(201, 'FRIDGE_CREATED', ['device_id' => $deviceId]);
    }

    public function getAll()
    {
        AuthMiddleware::verifyToken();
        $currentUser = AuthMiddleware::getCurrentUser();

        if ($currentUser['role'] === 'admin') {
            $stmt = $this->db->query("SELECT * FROM devices");
            $devices = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } elseif ($currentUser['role'] === 'client') {
            $devices = $this->deviceModel->getAllByUser($currentUser['id']);
        } else {
            $devices = $this->deviceModel->getAccessibleDevices($currentUser['id']);
        }

        return Response::json(200, 'SUCCESS', $devices);
    }

    public function getById($id)
    {
        AuthMiddleware::verifyToken();
        $currentUser = AuthMiddleware::getCurrentUser();
        $device = $this->deviceModel->getById($id);

        if (!$device) {
            return Response::json(404, 'NOT_FOUND');
        }

        if ($currentUser['role'] !== 'admin') {
            $isOwner = $device['user_id'] == $currentUser['id'];
            $hasAccess = $this->deviceModel->getAccess($id, $currentUser['id']);

            if ($currentUser['role'] === 'client' && !$isOwner) {
                return Response::json(403, 'ACCESS_DENIED');
            }

            if ($currentUser['role'] === 'visitor' && !$hasAccess) {
                return Response::json(403, 'ACCESS_DENIED');
            }
        }

        return Response::json(200, 'SUCCESS', $device);
    }

    public function update($id)
    {
        AuthMiddleware::verifyToken();
        $currentUser = AuthMiddleware::getCurrentUser();
        $device = $this->deviceModel->getById($id);

        if (!$device) {
            return Response::json(404, 'NOT_FOUND');
        }

        if ($currentUser['role'] !== 'admin') {
            $isOwner = $device['user_id'] == $currentUser['id'];
            $access = $this->deviceModel->getAccess($id, $currentUser['id']);

            if ($currentUser['role'] === 'client' && !$isOwner) {
                return Response::json(403, 'ACCESS_DENIED');
            }

            if ($currentUser['role'] === 'visitor' && (!$access || !$access['can_modify'])) {
                return Response::json(403, 'ACCESS_DENIED');
            }
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $changes = 0;
        foreach (['name', 'location', 'group_id', 'min_temp', 'max_temp', 'firmware_version'] as $field) {
            if (isset($data[$field]) && $data[$field] != $device[$field]) {
                $this->deviceModel->logChange($id, $field, $device[$field], $data[$field], $currentUser['id']);
                $changes++;
            }
        }

        if ($changes === 0) {
            return Response::json(200, 'NO_CHANGES');
        }

        $this->deviceModel->update($id, [
            'name' => $data['name'],
            'location' => $data['location'],
            'group_id' => $data['group_id'],
            'min_temp' => $data['min_temp'],
            'max_temp' => $data['max_temp'],
            'firmware_version' => $data['firmware_version']
        ]);

        return Response::json(200, 'FRIDGE_UPDATED');
    }

    public function delete($id)
    {
        AuthMiddleware::verifyToken();
        $currentUser = AuthMiddleware::getCurrentUser();
        $device = $this->deviceModel->getById($id);

        if (!$device) {
            return Response::json(404, 'NOT_FOUND');
        }

        if ($currentUser['role'] !== 'admin' && $device['user_id'] != $currentUser['id']) {
            return Response::json(403, 'ACCESS_DENIED');
        }

        $this->deviceModel->delete($id);
        return Response::json(200, 'FRIDGE_DELETED');
    }

    public function grantAccess($id)
    {
        AuthMiddleware::verifyToken();
        $currentUser = AuthMiddleware::getCurrentUser();

        if ($currentUser['role'] !== 'admin') {
            return Response::json(403, 'ACCESS_DENIED');
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['user_id'])) {
            return Response::json(400, 'MISSING_USER_ID');
        }

        $this->deviceModel->grantAccess($id, $data['user_id'], !empty($data['can_modify']));
        return Response::json(200, 'ACCESS_GRANTED');
    }

    public function revokeAccess($id)
    {
        AuthMiddleware::verifyToken();
        $currentUser = AuthMiddleware::getCurrentUser();

        if ($currentUser['role'] !== 'admin') {
            return Response::json(403, 'ACCESS_DENIED');
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['user_id'])) {
            return Response::json(400, 'MISSING_USER_ID');
        }

        $this->deviceModel->revokeAccess($id, $data['user_id']);
        return Response::json(200, 'ACCESS_REVOKED');
    }
}
