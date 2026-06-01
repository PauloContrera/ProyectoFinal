<?php

namespace Controllers;

use Models\Device;
use Helpers\AuditLogger;
use Helpers\Response;
use Helpers\Validator;
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

        $input = json_decode(file_get_contents("php://input"), true) ?: [];
        $name = trim($input['name'] ?? '');
        $deviceCode = strtoupper(trim($input['device_code'] ?? ''));
        if ($name === '') return Response::json(400, 'MISSING_NAME');
        if (!Validator::validateName($name)) return Response::json(400, 'INVALID_NAME');
        if ($deviceCode === '') return Response::json(400, 'MISSING_DEVICE_CODE');
        if (!Validator::validateDeviceCode($deviceCode)) return Response::json(400, 'INVALID_DEVICE_CODE');

        if ($currentUser['role'] === 'visitor') {
            return Response::json(403, 'ACCESS_DENIED');
        }

        $minTemp = (float)($input['min_temp'] ?? 2);
        $maxTemp = (float)($input['max_temp'] ?? 8);
        if (!Validator::validateTemperature($minTemp) || !Validator::validateTemperature($maxTemp) || $minTemp >= $maxTemp) {
            return Response::json(400, 'INVALID_TEMPERATURE_RANGE');
        }

        // Validar que no se repita el device_code
        global $db;
        $stmt = $db->prepare("SELECT id FROM devices WHERE device_code = ?");
        $stmt->execute([$deviceCode]);
        if ($stmt->fetch()) return Response::json(400, 'DEVICE_CODE_EXISTS');

        $isAdmin = in_array($currentUser['role'], ['admin', 'superadmin']);
        $targetUserId = $isAdmin && !empty($input['user_id'])
            ? (int)$input['user_id']
            : (int)$currentUser['id'];

        // Validar si el usuario a asignar existe y que no sea visitor.
        if ($targetUserId) {
            $userCheck = $db->prepare("SELECT id, role FROM users WHERE id = ?");
            $userCheck->execute([$targetUserId]);
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
        if (!empty($input['group_id']) && $targetUserId) {
            $checkOwnership = $db->prepare("SELECT id FROM device_groups WHERE id = ? AND user_id = ?");
            $checkOwnership->execute([$input['group_id'], $targetUserId]);
            if (!$checkOwnership->fetch()) return Response::json(403, 'GROUP_NOT_OWNED');
        }


        $location = empty($input['location']) ? null : trim((string)$input['location']);
        $firmwareVersion = empty($input['firmware_version']) ? null : trim((string)$input['firmware_version']);
        if (($location !== null && strlen($location) > 150) || ($firmwareVersion !== null && strlen($firmwareVersion) > 50)) {
            return Response::json(400, 'INVALID_DATA');
        }

        $macAddress = null;
        $sharedSecret = null;
        $activationKeyword = $_ENV['ESP_ACTIVATION_KEYWORD'] ?? 'clavesecreta4321';
        $sendInterval = 900;
        $protocolVersion = empty($input['protocol_version']) ? null : trim((string)$input['protocol_version']);

        if (!empty($input['mac_address'])) {
            $macAddress = $this->normalizeMac((string)$input['mac_address']);
            if (!$macAddress) {
                return Response::json(400, 'INVALID_DATA');
            }

            $macCheck = $db->prepare("SELECT id FROM devices WHERE mac_address = ?");
            $macCheck->execute([$macAddress]);
            if ($macCheck->fetch()) {
                return Response::json(400, 'MAC_ADDRESS_EXISTS');
            }

            $sharedSecret = bin2hex(random_bytes(32));
        }

        if (!empty($input['activation_keyword'])) {
            $activationKeyword = trim((string)$input['activation_keyword']);
        }
        if (strlen($activationKeyword) < 6 || strlen($activationKeyword) > 80) {
            return Response::json(400, 'INVALID_DATA');
        }

        if (isset($input['send_interval_seconds'])) {
            $sendInterval = max(60, min(86400, (int)$input['send_interval_seconds']));
        }

        if ($protocolVersion !== null && strlen($protocolVersion) > 20) {
            return Response::json(400, 'INVALID_DATA');
        }

        $data = [
            'device_code' => $deviceCode,
            'mac_address' => $macAddress,
            'shared_secret' => $sharedSecret,
            'activation_keyword' => $activationKeyword,
            'send_interval_seconds' => $sendInterval,
            'protocol_version' => $protocolVersion,
            'account_enabled' => true,
            'name' => $name,
            'location' => $location,
            'min_temp' => $minTemp,
            'max_temp' => $maxTemp,
            'firmware_version' => $firmwareVersion,
            'group_id' => empty($input['group_id']) ? null : (int)$input['group_id'],
            'user_id' => $targetUserId,
        ];

        $deviceId = $this->deviceModel->create($data);

        if (!$deviceId) {
            return Response::json(500, 'CREATE_FAILED');
        }

        $responseData = ['device_id' => (int)$deviceId];
        if ($macAddress && $sharedSecret) {
            $responseData['provisioning'] = [
                'device_id' => (int)$deviceId,
                'device_code' => $deviceCode,
                'mac_address' => $macAddress,
                'shared_secret' => $sharedSecret,
                'activation_keyword' => $activationKeyword,
                'register_endpoint' => '/api/esp/register',
                'sync_endpoint' => '/api/esp/sync',
                'signature' => 'HMAC_SHA256(mac + timestamp + json_data)',
            ];
            AuditLogger::event('esp_device_provisioned_by_admin', 'Dispositivo ESP preprovisionado desde administracion', 'info', [
                'device_id' => (int)$deviceId,
                'device_code' => $deviceCode,
                'mac_address' => $macAddress,
            ], (int)$currentUser['id'], 'device', (string)$deviceId, 'provision');
        }

        return Response::json(201, 'FRIDGE_CREATED', $responseData);
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
            $devices = $this->deviceModel->getAll();
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

        if ((int)$device['user_id'] !== (int)$user['id'] && $user['role'] === 'client') {
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

        if ($user['role'] === 'visitor') {
            return Response::json(403, 'ACCESS_DENIED');
        }

        $isOwner = (int)$device['user_id'] === (int)$user['id'];
        $isAdmin = in_array($user['role'], ['admin', 'superadmin']);
        $hasAccess = $this->deviceModel->getAccess($id, $user['id']);

        if (!$isOwner && !$isAdmin && (!$hasAccess || !$hasAccess['can_modify'])) {
            return Response::json(403, 'ACCESS_DENIED');
        }

        $input = json_decode(file_get_contents("php://input"), true) ?: [];
        $name = trim($input['name'] ?? '');
        if ($name === '') return Response::json(400, 'MISSING_NAME');
        if (!Validator::validateName($name)) return Response::json(400, 'INVALID_NAME');

        $minTemp = (float)($input['min_temp'] ?? 0);
        $maxTemp = (float)($input['max_temp'] ?? 10);
        if (!Validator::validateTemperature($minTemp) || !Validator::validateTemperature($maxTemp) || $minTemp >= $maxTemp) {
            return Response::json(400, 'INVALID_TEMPERATURE_RANGE');
        }

        $location = empty($input['location']) ? null : trim((string)$input['location']);
        $firmwareVersion = empty($input['firmware_version']) ? null : trim((string)$input['firmware_version']);
        if (($location !== null && strlen($location) > 150) || ($firmwareVersion !== null && strlen($firmwareVersion) > 50)) {
            return Response::json(400, 'INVALID_DATA');
        }

        $data = [
            'name' => $name,
            'location' => $location,
            'min_temp' => $minTemp,
            'max_temp' => $maxTemp,
            'firmware_version' => $firmwareVersion
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

        if ($user['role'] === 'visitor') {
            return Response::json(403, 'ACCESS_DENIED');
        }

        $isOwner = (int)$device['user_id'] === (int)$user['id'];
        $isAdmin = in_array($user['role'], ['admin', 'superadmin']);
        if (!$isOwner && !$isAdmin) return Response::json(403, 'ACCESS_DENIED');

        $this->deviceModel->delete($id, $user['id']);
        return Response::json(200, 'FRIDGE_DELETED');
    }

    public function grantAccess($id)
    {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];
        $input = json_decode(file_get_contents("php://input"), true) ?: [];

        if (empty($input['user_id'])) return Response::json(400, 'MISSING_USER_ID');

        $device = $this->deviceModel->getById($id);
        if (!$device) {
            return in_array($user['role'], ['admin', 'superadmin'])
                ? Response::json(404, 'FRIDGE_NOT_FOUND')
                : Response::json(403, 'ACCESS_DENIED');
        }

        if ($user['role'] === 'visitor') {
            return Response::json(403, 'ACCESS_DENIED');
        }

        if ((int)$device['user_id'] !== (int)$user['id'] && !in_array($user['role'], ['admin', 'superadmin'])) {
            return Response::json(403, 'ACCESS_DENIED');
        }



        // ✅ Verificar si el usuario existe
        global $db;
        $stmt = $db->prepare("SELECT id, role FROM users WHERE id = ?");
        $stmt->execute([$input['user_id']]);
        $targetUser = $stmt->fetch();
        if (!$targetUser) {
            return Response::json(404, 'USER_NOT_FOUND');
        }

        $canModify = $targetUser['role'] === 'visitor' ? false : (bool)($input['can_modify'] ?? false);
        $this->deviceModel->grantAccess($id, $input['user_id'], $canModify);

        $this->deviceModel->logAccessChange($id, $user['id'], $input['user_id'], 'grant', $canModify);
        return Response::json(200, 'ACCESS_GRANTED');
    }


    public function revokeAccess($id)
    {
        AuthMiddleware::verifyToken();


        $user = $_SERVER['user'];
        $input = json_decode(file_get_contents("php://input"), true) ?: [];

        if (empty($input['user_id'])) {
            return Response::json(400, 'MISSING_USER_ID');
        }

        $device = $this->deviceModel->getById($id);
        if ($user['role'] === 'visitor') {
            return Response::json(403, 'ACCESS_DENIED');
        }

        if (!$device || ((int)$device['user_id'] !== (int)$user['id'] && !in_array($user['role'], ['admin', 'superadmin']))) {
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

        $input = json_decode(file_get_contents("php://input"), true) ?: [];
        if (empty($input['device_code']) || empty($input['user_id'])) {
            return Response::json(400, 'MISSING_DATA');
        }
        $deviceCode = strtoupper(trim((string)$input['device_code']));
        if (!Validator::validateDeviceCode($deviceCode)) return Response::json(400, 'INVALID_DEVICE_CODE');
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


        $result = $this->deviceModel->assignToUser($deviceCode, (int)$input['user_id']);
        if (isset($result['error'])) {
            return Response::json(400, $result['error']);
        }

        return Response::json(200, 'DEVICE_ASSIGNED');
    }
    public function assignGroup($id)
    {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];
        $input = json_decode(file_get_contents("php://input"), true) ?: [];

        if ($user['role'] === 'visitor') {
            return Response::json(403, 'ACCESS_DENIED');
        }

        if (!array_key_exists('group_id', $input)) return Response::json(400, 'MISSING_GROUP');

        $device = $this->deviceModel->getById($id);
        if (!$device) {
            return in_array($user['role'], ['admin', 'superadmin'])
                ? Response::json(404, 'FRIDGE_NOT_FOUND')
                : Response::json(403, 'ACCESS_DENIED');
        }

        $isOwner = (int)$device['user_id'] === (int)$user['id'];
        $isAdmin = in_array($user['role'], ['admin', 'superadmin']);
        if (!$isOwner && !$isAdmin) return Response::json(403, 'ACCESS_DENIED');

        $groupId = $input['group_id'] === null || $input['group_id'] === '' ? null : (int)$input['group_id'];
        global $db;
        if ($groupId !== null) {
            $stmt = $db->prepare("SELECT id FROM device_groups WHERE id = ?");
            $stmt->execute([$groupId]);
            if (!$stmt->fetch()) return Response::json(400, 'MISSING_GROUP');

            if (!$this->deviceModel->groupBelongsToUser($groupId, $device['user_id'])) {
                return Response::json(403, 'ACCESS_DENIED');
            }
        }
        $updated = $this->deviceModel->assignGroup($id, $groupId, $user['id']);
        return $updated ? Response::json(200, 'GROUP_ASSIGNED') : Response::json(200, 'NO_CHANGES');
    }

    private function normalizeMac(string $mac): ?string
    {
        $compact = strtoupper(preg_replace('/[^A-Fa-f0-9]/', '', $mac));
        if (strlen($compact) !== 12) {
            return null;
        }

        return implode(':', str_split($compact, 2));
    }
}
