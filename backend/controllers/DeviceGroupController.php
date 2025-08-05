<?php

namespace Controllers;

use Models\DeviceGroup;
use Helpers\Response;
use Middleware\AuthMiddleware;

class DeviceGroupController {
    private $groupModel;

    public function __construct($db) {
        $this->groupModel = new DeviceGroup($db);
    }

    public function create() {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];

        if ($user['role'] === 'visitor') {
        return Response::json(403, 'CANNOT_ASSIGN_TO_VISITOR');
    }
        $input = json_decode(file_get_contents("php://input"), true);
        if (empty($input['name'])) return Response::json(400, 'MISSING_NAME');

        $data = [
            'name' => $input['name'],
            'description' => $input['description'] ?? null,
            'user_id' => $user['id']
        ];

        $groupId = $this->groupModel->create($data);
        return Response::json(201, 'GROUP_CREATED', ['group_id' => $groupId]);
    }

    public function getAll() {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];

        if (in_array($user['role'], ['admin', 'superadmin'])) {
            global $db;
            $stmt = $db->query("SELECT * FROM device_groups");
            $groups = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $groups = $this->groupModel->getAllByUser($user['id']);
        }

        return Response::json(200, null, $groups);
    }

    public function getOne($id) {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];

        $group = $this->groupModel->getById($id);
            if (!$group) {
        // Solo mostrar NOT_FOUND a administradores
        if (in_array($user['role'], ['admin', 'superadmin'])) {
            return Response::json(404, 'NOT_FOUND');
        } else {
            return Response::json(403, 'ACCESS_DENIED');
        }
    }


        if ($group['user_id'] != $user['id'] && !in_array($user['role'], ['admin', 'superadmin'])) {
            return Response::json(403, 'ACCESS_DENIED');
        }

        return Response::json(200, null, $group);
    }

public function update($id) {
    AuthMiddleware::verifyToken();
    $user = $_SERVER['user'];

    $group = $this->groupModel->getById($id);
    if (!$group) {
        if (in_array($user['role'], ['admin', 'superadmin'])) {
            return Response::json(404, 'NOT_FOUND');
        } else {
            return Response::json(403, 'ACCESS_DENIED');
        }
    }

    // ✅ Solo puede editar si es dueño o admin/superadmin
    if ($group['user_id'] != $user['id'] && !in_array($user['role'], ['admin', 'superadmin'])) {
        return Response::json(403, 'ACCESS_DENIED');
    }

    $input = json_decode(file_get_contents("php://input"), true);
    if (empty($input['name'])) return Response::json(400, 'MISSING_NAME');

    $data = [
        'name' => $input['name'],
        'description' => $input['description'] ?? null
    ];

    $updated = $this->groupModel->update($id, $data, $user['id']);
    return $updated ? Response::json(200, 'GROUP_UPDATED') : Response::json(200, 'NO_CHANGES');
}


public function delete($id) {
    AuthMiddleware::verifyToken();
    $user = $_SERVER['user'];

    $group = $this->groupModel->getById($id);
    if (!$group) {
        if (in_array($user['role'], ['admin', 'superadmin'])) {
            return Response::json(404, 'NOT_FOUND');
        } else {
            return Response::json(403, 'ACCESS_DENIED');
        }
    }

    if ($group['user_id'] != $user['id'] && !in_array($user['role'], ['admin', 'superadmin'])) {
        return Response::json(403, 'ACCESS_DENIED');
    }

    if ($this->groupModel->hasDevices($id)) {
        return Response::json(400, 'GROUP_HAS_DEVICES');
    }

    $this->groupModel->delete($id, $user['id']);
    return Response::json(200, 'GROUP_DELETED');
}


}
