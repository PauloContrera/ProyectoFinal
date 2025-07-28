<?php

namespace Models;

use PDO;

class Device
{
    private $conn;
    private $table = 'devices';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create($data)
    {
        $stmt = $this->conn->prepare("
        INSERT INTO devices (
            device_code, name, location, min_temp, max_temp,
            firmware_version, group_id, user_id
        ) VALUES (
            :device_code, :name, :location, :min_temp, :max_temp,
            :firmware_version, :group_id, :user_id
        )
    ");

        $stmt->execute([
            ':device_code' => $data['device_code'],
            ':name' => $data['name'],
            ':location' => $data['location'],
            ':min_temp' => $data['min_temp'],
            ':max_temp' => $data['max_temp'],
            ':firmware_version' => $data['firmware_version'],
            ':group_id' => $data['group_id'],
            ':user_id' => $data['user_id']
        ]);

        $id = $this->conn->lastInsertId();

        if ($data['user_id']) {
            $this->logFullChange($id, $data['user_id'], 'create', $data);
        } else {
            $this->logFullChange($id, null, 'create', $data);
        }

        return $id;
    }

    public function getById($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllByUser($userId)
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAccessibleDevices($userId)
    {
        $stmt = $this->conn->prepare("
            SELECT d.* FROM {$this->table} d
            LEFT JOIN device_access da ON d.id = da.device_id
            WHERE d.user_id = :uid OR da.user_id = :uid
        ");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update($id, $data, $userId)
    {
        $current = $this->getById($id);
        if (!$current) return 0;

        $stmt = $this->conn->prepare("
            UPDATE {$this->table}
            SET name = :name,
                location = :location,
                min_temp = :min_temp,
                max_temp = :max_temp,
                firmware_version = :firmware_version
            WHERE id = :id
        ");
        $stmt->execute([
            ':name' => $data['name'],
            ':location' => $data['location'],
            ':min_temp' => $data['min_temp'],
            ':max_temp' => $data['max_temp'],
            ':firmware_version' => $data['firmware_version'],
            ':id' => $id
        ]);

        $this->logDifferences($id, $userId, $current, $data);
        return $stmt->rowCount();
    }

    public function delete($id, $userId)
    {
        $current = $this->getById($id);
        $this->logFullChange($id, $userId, 'delete', $current);

        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }

    private function logDifferences($deviceId, $userId, $old, $new)
    {
        $campos = ['name', 'location', 'min_temp', 'max_temp', 'firmware_version'];
        foreach ($campos as $campo) {
            $oldValue = $old[$campo];
            $newValue = $new[$campo] ?? null;
            if ($oldValue != $newValue) {
                $this->insertLog($deviceId, $userId, 'update', $campo, $oldValue, $newValue);
            }
        }
    }

    private function logFullChange($deviceId, $userId, $action, $data)
    {
        foreach (['name', 'location', 'min_temp', 'max_temp', 'firmware_version', 'group_id'] as $campo) {
            $value = $data[$campo] ?? null;
            if ($action === 'create') {
                $this->insertLog($deviceId, $userId, $action, $campo, null, $value);
            } elseif ($action === 'delete') {
                $this->insertLog($deviceId, $userId, $action, $campo, $value, null);
            }
        }
    }



    // Accesos
    public function grantAccess($deviceId, $userId, $canModify)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO device_access (device_id, user_id, can_modify)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE can_modify = VALUES(can_modify)
        ");
        return $stmt->execute([$deviceId, $userId, $canModify]);
    }

    public function revokeAccess($deviceId, $userId)
    {
        $stmt = $this->conn->prepare("DELETE FROM device_access WHERE device_id = ? AND user_id = ?");
        return $stmt->execute([$deviceId, $userId]);
    }

    public function getAccess($deviceId, $userId)
    {
        $stmt = $this->conn->prepare("SELECT * FROM device_access WHERE device_id = ? AND user_id = ?");
        $stmt->execute([$deviceId, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function logAccessChange($deviceId, $changedBy, $targetUserId, $action, $canModify = null)
    {
        $stmt = $this->conn->prepare("
        INSERT INTO device_access_log (device_id, target_user, changed_by, action, can_modify)
        VALUES (?, ?, ?, ?, ?)
    ");
        $stmt->execute([
            $deviceId,
            $targetUserId,
            $changedBy,
            $action,
            $canModify
        ]);
    }
    public function assignToUser($deviceCode, $userId)
    {
        $stmt = $this->conn->prepare("SELECT id, user_id FROM devices WHERE device_code = ?");
        $stmt->execute([$deviceCode]);
        $device = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$device) return ['error' => 'DEVICE_NOT_FOUND'];
        if (!empty($device['user_id'])) return ['error' => 'ALREADY_ASSIGNED'];

        $stmt = $this->conn->prepare("UPDATE devices SET user_id = ? WHERE device_code = ?");
        $stmt->execute([$userId, $deviceCode]);
        $this->insertLog(
            $device['id'],
            $userId,
            'update',
            'user_id',
            null,
            $userId
        );
        return ['success' => true];
    }
    public function getUnassigned()
    {
        $stmt = $this->conn->prepare("SELECT * FROM devices WHERE user_id IS NULL");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function groupBelongsToUser($groupId, $userId)
    {
        $stmt = $this->conn->prepare("SELECT id FROM device_groups WHERE id = ? AND user_id = ?");
        $stmt->execute([$groupId, $userId]);
        return $stmt->fetch() !== false;
    }

    public function assignGroup($deviceId, $groupId, $userId)
    {
        $current = $this->getById($deviceId);
        if (!$current || $current['group_id'] == $groupId) return 0;

        $stmt = $this->conn->prepare("UPDATE devices SET group_id = :group_id WHERE id = :id");
        $stmt->execute([':group_id' => $groupId, ':id' => $deviceId]);

        $this->insertLog(
            $deviceId,
            $userId,
            'update',
            'group_id',
            $current['group_id'],
            $groupId
        );


        return $stmt->rowCount();
    }
    private function insertLog($deviceId, $userId, $action, $field, $oldValue, $newValue)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO device_change_log (device_id, user_id, action, field_changed, old_value, new_value)
            VALUES (:device_id, :user_id, :action, :field_changed, :old_value, :new_value)
        ");
        $stmt->execute([
            ':device_id' => $deviceId,
            ':user_id' => $userId,
            ':action' => $action,
            ':field_changed' => $field,
            ':old_value' => $oldValue,
            ':new_value' => $newValue
        ]);
    }
}
