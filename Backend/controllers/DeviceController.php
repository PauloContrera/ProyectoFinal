<?php

namespace Controllers;

use Models\Device;
use Helpers\Response;
use Middleware\AuthMiddleware;

class DeviceController {
    private $deviceModel;

    public function __construct($db) {
        $this->deviceModel = new Device($db);
    }

    public function create() {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];

        $input = json_decode(file_get_contents("php://input"), true);
        if (empty($input['name'])) return Response::json(400, 'MISSING_NAME');

        $data = [
            'name' => $input['name'],
            'location' => $input['location'] ?? null,
            'min_temp' => $input['min_temp'] ?? 0,
            'max_temp' => $input['max_temp'] ?? 10,
            'firmware_version' => $input['firmware_version'] ?? null,
            'group_id' => $input['group_id'] ?? null,
            'user_id' => $user['id']
        ];

        if ($data['group_id']) {
            global $db;
            $stmt = $db->prepare("SELECT id FROM device_groups WHERE id = ?");
            $stmt->execute([$data['group_id']]);
            if (!$stmt->fetch()) return Response::json(400, 'MISSING_GROUP');
        }

        $deviceId = $this->deviceModel->create($data);
        return $deviceId
            ? Response::json(201, 'FRIDGE_CREATED', ['device_id' => $deviceId])
            : Response::json(500, 'CREATE_FAILED');
    }

    public function getAll() {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];

        if (in_array($user['role'], ['admin', 'superadmin'])) {
            global $db;
            $stmt = $db->query("SELECT * FROM devices");
            $devices = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } elseif ($user['role'] === 'client') {
            $devices = $this->deviceModel->getAllByUser($user['id']);
        } else {
            $devices = $this->deviceModel->getAccessibleDevices($user['id']);
        }

        return Response::json(200, 'FRIDGE_LIST', $devices);
    }

    public function getOne($id) {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];

        $device = $this->deviceModel->getById($id);
        if (!$device) {
        // Solo mostrar NOT_FOUND a administradores
        if (in_array($user['role'], ['admin', 'superadmin'])) {
            return Response::json(404, 'FRIDGE_NOT_FOUND');
        } else {
            return Response::json(403, 'ACCESS_DENIED');
        }
    }

        if ($device['user_id'] !== $user['id'] && $user['role'] === 'client') {
            return Response::json(403, 'ACCESS_DENIED');
        }

        if ($user['role'] === 'visitor') {
            $access = $this->deviceModel->getAccess($id, $user['id']);
            if (!$access) return Response::json(403, 'ACCESS_DENIED');
        }

        return Response::json(200, null, $device);
    }

    public function update($id) {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];
        $device = $this->deviceModel->getById($id);
        if (!$device) {
        // Solo mostrar NOT_FOUND a administradores
        if (in_array($user['role'], ['admin', 'superadmin'])) {
            return Response::json(404, 'FRIDGE_NOT_FOUND');
        } else {
            return Response::json(403, 'ACCESS_DENIED');
        }
    }

        $isOwner = $device['user_id'] === $user['id'];
        $isAdmin = in_array($user['role'], ['admin', 'superadmin']);
        $hasAccess = $this->deviceModel->getAccess($id, $user['id']);

        if (!$isOwner && !$isAdmin && (!$hasAccess || !$hasAccess['can_modify'])) {
            return Response::json(403, 'ACCESS_DENIED');
        }

        $input = json_decode(file_get_contents("php://input"), true);
        if (empty($input['name'])) return Response::json(400, 'MISSING_NAME');

        $data = [
            'name' => $input['name'],
            'location' => $input['location'] ?? null,
            'min_temp' => $input['min_temp'] ?? 0,
            'max_temp' => $input['max_temp'] ?? 10,
            'firmware_version' => $input['firmware_version'] ?? null
        ];

        $updated = $this->deviceModel->update($id, $data, $user['id']);
        return $updated ? Response::json(200, 'FRIDGE_UPDATED') : Response::json(200, 'NO_CHANGES');
    }

    public function delete($id) {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];
        $device = $this->deviceModel->getById($id);
        if (!$device) {
        // Solo mostrar NOT_FOUND a administradores
        if (in_array($user['role'], ['admin', 'superadmin'])) {
            return Response::json(404, 'FRIDGE_NOT_FOUND');
        } else {
            return Response::json(403, 'ACCESS_DENIED');
        }
    }

        $isOwner = $device['user_id'] === $user['id'];
        $isAdmin = in_array($user['role'], ['admin', 'superadmin']);
        if (!$isOwner && !$isAdmin) return Response::json(403, 'ACCESS_DENIED');

        $this->deviceModel->delete($id, $user['id']);
        return Response::json(200, 'FRIDGE_DELETED');
    }

    public function grantAccess($id) {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['user_id'])) return Response::json(400, 'MISSING_USER_ID');

        $device = $this->deviceModel->getById($id);
        if (!$device || $device['user_id'] !== $user['id']) return Response::json(403, 'ACCESS_DENIED');

        $canModify = $input['can_modify'] ?? false;
        $this->deviceModel->grantAccess($id, $input['user_id'], $canModify);
        return Response::json(200, 'ACCESS_GRANTED');
    }

    public function revokeAccess($id) {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['user_id'])) return Response::json(400, 'MISSING_USER_ID');

        $device = $this->deviceModel->getById($id);
        if (!$device || $device['user_id'] !== $user['id']) return Response::json(403, 'ACCESS_DENIED');

        $this->deviceModel->revokeAccess($id, $input['user_id']);
        return Response::json(200, 'ACCESS_REVOKED');
    }
}
