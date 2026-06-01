<?php

namespace Models;

use Helpers\AuditLogger;
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
            device_code, mac_address, shared_secret, account_enabled,
            activation_keyword, send_interval_seconds, protocol_version,
            name, location, min_temp, max_temp,
            firmware_version, group_id, user_id
        ) VALUES (
            :device_code, :mac_address, :shared_secret, :account_enabled,
            :activation_keyword, :send_interval_seconds, :protocol_version,
            :name, :location, :min_temp, :max_temp,
            :firmware_version, :group_id, :user_id
        )
    ");

        $stmt->execute([
            ':device_code' => $data['device_code'],
            ':mac_address' => $data['mac_address'] ?? null,
            ':shared_secret' => $data['shared_secret'] ?? null,
            ':account_enabled' => array_key_exists('account_enabled', $data) ? (int)(bool)$data['account_enabled'] : 1,
            ':activation_keyword' => $data['activation_keyword'] ?? ($_ENV['ESP_ACTIVATION_KEYWORD'] ?? 'clavesecreta4321'),
            ':send_interval_seconds' => $data['send_interval_seconds'] ?? 900,
            ':protocol_version' => $data['protocol_version'] ?? null,
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
        $stmt = $this->conn->prepare("
            SELECT {$this->publicDeviceColumns('d')}
            FROM {$this->table} d
            WHERE d.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll()
    {
        $stmt = $this->conn->query($this->selectWithLatestTemperature() . " ORDER BY d.created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllByUser($userId)
    {
        $stmt = $this->conn->prepare($this->selectWithLatestTemperature() . " WHERE d.user_id = ? ORDER BY d.created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAccessibleDevices($userId)
    {
        $stmt = $this->conn->prepare("
            {$this->selectWithLatestTemperature()}
            LEFT JOIN device_access da ON d.id = da.device_id
            WHERE d.user_id = :owner_uid OR da.user_id = :access_uid
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([
            ':owner_uid' => $userId,
            ':access_uid' => $userId
        ]);
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

        AuditLogger::event('device_access_change', "Acceso de heladera {$action}", 'info', [
            'device_id' => $deviceId,
            'target_user_id' => $targetUserId,
            'can_modify' => $canModify,
        ], $changedBy ? (int)$changedBy : null, 'device', (string)$deviceId, $action);
    }
    public function assignToUser($deviceCode, $userId)
    {
        $stmt = $this->conn->prepare("SELECT * FROM devices WHERE device_code = ?");
        $stmt->execute([$deviceCode]);
        $device = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$device) return ['error' => 'DEVICE_NOT_FOUND'];
        if ($device['user_id']) return ['error' => 'DEVICE_ALREADY_ASSIGNED'];

        $stmt = $this->conn->prepare("UPDATE devices SET user_id = :user_id WHERE device_code = :device_code");
        $stmt->execute([
            ':user_id' => $userId,
            ':device_code' => $deviceCode
        ]);

        // log del cambio de asignación de usuario
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
        $stmt = $this->conn->prepare("
            SELECT {$this->publicDeviceColumns('d')}
            FROM devices d
            WHERE d.user_id IS NULL
        ");
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
    public function getByCode($code)
    {
        $stmt = $this->conn->prepare("
            SELECT {$this->publicDeviceColumns('d')}
            FROM devices d
            WHERE d.device_code = ?
        ");
        $stmt->execute([$code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function selectWithLatestTemperature()
    {
        return "
            SELECT
                {$this->publicDeviceColumns('d')},
                (
                    SELECT t.id
                    FROM temperatures t
                    WHERE t.device_id = d.id
                    ORDER BY t.recorded_at DESC, t.id DESC
                    LIMIT 1
                ) AS last_temperature_id,
                (
                    SELECT t.temperature
                    FROM temperatures t
                    WHERE t.device_id = d.id
                    ORDER BY t.recorded_at DESC, t.id DESC
                    LIMIT 1
                ) AS last_temperature,
                (
                    SELECT t.recorded_at
                    FROM temperatures t
                    WHERE t.device_id = d.id
                    ORDER BY t.recorded_at DESC, t.id DESC
                    LIMIT 1
                ) AS last_temperature_recorded_at
            FROM {$this->table} d
        ";
    }

    private function publicDeviceColumns(string $alias): string
    {
        $columns = [
            'id',
            'device_code',
            'mac_address',
            'account_enabled',
            'send_interval_seconds',
            'protocol_version',
            'config_version',
            'last_config_version_sent',
            'registered_model',
            'sim_imei',
            'last_sync_at',
            'last_sequence',
            'last_packet_id',
            'last_packet_at',
            'name',
            'location',
            'user_id',
            'group_id',
            'min_temp',
            'max_temp',
            'firmware_version',
            'last_reported_at',
            'device_time',
            'time_discrepancy',
            'created_at',
        ];

        return implode(",\n                ", array_map(
            fn($column) => "{$alias}.`{$column}` AS `{$column}`",
            $columns
        ));
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

        AuditLogger::event('device_change', "Cambio de heladera: {$action}", 'info', [
            'field_changed' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ], $userId ? (int)$userId : null, 'device', (string)$deviceId, $action);
    }
}
