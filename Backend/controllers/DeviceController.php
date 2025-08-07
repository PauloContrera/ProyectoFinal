<?php

namespace Controllers;

use Models\Device;
use Helpers\Response;
use Middleware\AuthMiddleware;

class DeviceController
{
    private $deviceModel;

    public function __construct($db)
    {
        $this->deviceModel = new Device($db);
    }

    public function create()
    {
        AuthMiddleware::verifyToken();
        $currentUser = $_SERVER['user'];

        // Solo admin o superadmin pueden crear dispositivos
        if (!in_array($currentUser['role'], ['admin', 'superadmin'])) {
            return Response::json(403, 'ACCESS_DENIED');
        }

        $input = json_decode(file_get_contents("php://input"), true);
        if (empty($input['name'])) return Response::json(400, 'MISSING_NAME');
        if (empty($input['device_code'])) return Response::json(400, 'MISSING_DEVICE_CODE');

        // Validar que no se repita el device_code
        global $db;
        $stmt = $db->prepare("SELECT id FROM devices WHERE device_code = ?");
        $stmt->execute([$input['device_code']]);
        if ($stmt->fetch()) return Response::json(400, 'DEVICE_CODE_EXISTS');

        // Validar si el usuario a asignar existe (opcional)
        // Validar si el usuario a asignar existe y que no sea visitor (opcional)
        if (!empty($input['user_id'])) {
            $userCheck = $db->prepare("SELECT id, role FROM users WHERE id = ?");
            $userCheck->execute([$input['user_id']]);
            $user = $userCheck->fetch();

            if (!$user) return Response::json(400, 'USER_NOT_FOUND');
            if ($user['role'] === 'visitor') return Response::json(403, 'CANNOT_ASSIGN_TO_VISITOR');
        }


        // Validar si el grupo existe (opcional)
        if (!empty($input['group_id'])) {
            $groupCheck = $db->prepare("SELECT id FROM device_groups WHERE id = ?");
            $groupCheck->execute([$input['group_id']]);
            if (!$groupCheck->fetch()) return Response::json(400, 'MISSING_GROUP');
        }

        // Validar que el grupo pertenezca al usuario asignado
        if (!empty($input['group_id']) && !empty($input['user_id'])) {
            $checkOwnership = $db->prepare("SELECT id FROM device_groups WHERE id = ? AND user_id = ?");
            $checkOwnership->execute([$input['group_id'], $input['user_id']]);
            if (!$checkOwnership->fetch()) return Response::json(403, 'GROUP_NOT_OWNED');
        }


        $data = [
            'device_code' => $input['device_code'],
            'name' => $input['name'],
            'location' => $input['location'] ?? null,
            'min_temp' => $input['min_temp'] ?? 0,
            'max_temp' => $input['max_temp'] ?? 10,
            'firmware_version' => $input['firmware_version'] ?? null,
            'group_id' => $input['group_id'] ?? null,
            'user_id' => $input['user_id'] ?? null,
        ];

        $deviceId = $this->deviceModel->create($data);

        return $deviceId
            ? Response::json(201, 'FRIDGE_CREATED', ['device_id' => $deviceId])
            : Response::json(500, 'CREATE_FAILED');
    }

    public function getUnassigned()
    {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];

        if (!in_array($user['role'], ['admin', 'superadmin'])) {
            return Response::json(403, 'ACCESS_DENIED');
        }

        $devices = $this->deviceModel->getUnassigned();
        return Response::json(200, 'UNASSIGNED_LIST', $devices);
    }


    public function getAll()
    {
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

    public function getOne($id)
    {
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

    public function update($id)
    {
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

    public function delete($id)
    {
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

    public function grantAccess($id)
    {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['user_id'])) return Response::json(400, 'MISSING_USER_ID');

        $device = $this->deviceModel->getById($id);
        if (!$device) {
            return in_array($user['role'], ['admin', 'superadmin'])
                ? Response::json(404, 'FRIDGE_NOT_FOUND')
                : Response::json(403, 'ACCESS_DENIED');
        }

        if ($device['user_id'] !== $user['id'] && !in_array($user['role'], ['admin', 'superadmin'])) {
            return Response::json(403, 'ACCESS_DENIED');
        }



        // ✅ Verificar si el usuario existe
        global $db;
        $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$input['user_id']]);
        if (!$stmt->fetch()) {
            return Response::json(404, 'USER_NOT_FOUND');
        }

        $canModify = $input['can_modify'] ?? false;
        $this->deviceModel->grantAccess($id, $input['user_id'], $canModify);

        $this->deviceModel->logAccessChange($id, $user['id'], $input['user_id'], 'grant', $canModify);
        return Response::json(200, 'ACCESS_GRANTED');
    }


    public function revokeAccess($id)
    {
        AuthMiddleware::verifyToken();


        $user = $_SERVER['user'];
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['user_id'])) {
            return Response::json(400, 'MISSING_USER_ID');
        }

        $device = $this->deviceModel->getById($id);
        if (!$device || ($device['user_id'] !== $user['id'] && !in_array($user['role'], ['admin', 'superadmin']))) {
            return Response::json(403, 'ACCESS_DENIED');
        }


        // ✅ Validar que el usuario exista
        global $db;
        $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$input['user_id']]);
        if (!$stmt->fetch()) {
            return Response::json(404, 'USER_NOT_FOUND');
        }

        // ✅ Verificar si el usuario tenía acceso
        $access = $this->deviceModel->getAccess($id, $input['user_id']);
        if (!$access) {
            return Response::json(404, 'ACCESS_NOT_FOUND');
        }

        // ✅ Revocar y loguear
        $this->deviceModel->revokeAccess($id, $input['user_id']);
        $this->deviceModel->logAccessChange($id, $user['id'], $input['user_id'], 'revoke');

        return Response::json(200, 'ACCESS_REVOKED');
    }
    public function assignToUser()
    {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];

        if (!in_array($user['role'], ['admin', 'superadmin'])) {
            return Response::json(403, 'ACCESS_DENIED');
        }

        $input = json_decode(file_get_contents("php://input"), true);
        if (empty($input['device_code']) || empty($input['user_id'])) {
            return Response::json(400, 'MISSING_DATA');
        }
        // Validar que el usuario a asignar exista
        global $db;

        // Validar que el usuario a asignar exista y no sea visitor
        $userCheck = $db->prepare("SELECT id, role FROM users WHERE id = ?");
        $userCheck->execute([$input['user_id']]);
        $targetUser = $userCheck->fetch();

        if (!$targetUser) {
            return Response::json(400, 'USER_NOT_FOUND');
        }

        if ($targetUser['role'] === 'visitor') {
            return Response::json(403, 'CANNOT_ASSIGN_TO_VISITOR');
        }


        $result = $this->deviceModel->assignToUser($input['device_code'], $input['user_id']);
        if (isset($result['error'])) {
            return Response::json(400, $result['error']);
        }

        return Response::json(200, 'DEVICE_ASSIGNED');
    }
    public function assignGroup($id)
    {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];
        $input = json_decode(file_get_contents("php://input"), true);

        if (empty($input['group_id'])) return Response::json(400, 'MISSING_GROUP');

        $device = $this->deviceModel->getById($id);
        if (!$device) {
            return in_array($user['role'], ['admin', 'superadmin'])
                ? Response::json(404, 'FRIDGE_NOT_FOUND')
                : Response::json(403, 'ACCESS_DENIED');
        }

        $isOwner = $device['user_id'] === $user['id'];
        $isAdmin = in_array($user['role'], ['admin', 'superadmin']);
        if (!$isOwner && !$isAdmin) return Response::json(403, 'ACCESS_DENIED');

        $groupId = $input['group_id'];
        global $db;
        $stmt = $db->prepare("SELECT id FROM device_groups WHERE id = ?");
        $stmt->execute([$groupId]);
        if (!$stmt->fetch()) return Response::json(400, 'MISSING_GROUP');

        if (!$this->deviceModel->groupBelongsToUser($groupId, $device['user_id'])) {
            return Response::json(403, 'ACCESS_DENIED');
        }
        $updated = $this->deviceModel->assignGroup($id, $groupId, $user['id']);
        return $updated ? Response::json(200, 'GROUP_ASSIGNED') : Response::json(200, 'NO_CHANGES');
    }
}
