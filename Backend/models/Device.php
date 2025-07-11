<?php

namespace Models;

use PDO;

class Device {
    private $conn;
    private $table = 'devices';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} 
            (name, location, user_id, group_id, min_temp, max_temp, firmware_version)
            VALUES (:name, :location, :user_id, :group_id, :min_temp, :max_temp, :firmware_version)");
        $stmt->execute([
            ':name' => $data['name'],
            ':location' => $data['location'],
            ':user_id' => $data['user_id'],
            ':group_id' => $data['group_id'],
            ':min_temp' => $data['min_temp'],
            ':max_temp' => $data['max_temp'],
            ':firmware_version' => $data['firmware_version']
        ]);
        $id = $this->conn->lastInsertId();
        $this->logChange($id, $data['user_id'], 'create', json_encode($data));
        return $id;
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllByUser($userId) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAccessibleDevices($userId) {
        $stmt = $this->conn->prepare("
            SELECT d.* FROM {$this->table} d
            LEFT JOIN device_access da ON d.id = da.device_id
            WHERE d.user_id = :uid OR da.user_id = :uid
        ");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update($id, $data, $userId) {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table}
            SET name = :name, location = :location, min_temp = :min_temp,
                max_temp = :max_temp, firmware_version = :firmware_version
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
        $this->logChange($id, $userId, 'update', json_encode($data));
        return $stmt->rowCount();
    }

    public function delete($id, $userId) {
        $this->logChange($id, $userId, 'delete', null);
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }

    public function grantAccess($deviceId, $userId, $canModify) {
        $stmt = $this->conn->prepare("
            INSERT INTO device_access (device_id, user_id, can_modify)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE can_modify = VALUES(can_modify)
        ");
        return $stmt->execute([$deviceId, $userId, $canModify]);
    }

    public function revokeAccess($deviceId, $userId) {
        $stmt = $this->conn->prepare("DELETE FROM device_access WHERE device_id = ? AND user_id = ?");
        return $stmt->execute([$deviceId, $userId]);
    }

    public function getAccess($deviceId, $userId) {
        $stmt = $this->conn->prepare("SELECT * FROM device_access WHERE device_id = ? AND user_id = ?");
        $stmt->execute([$deviceId, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function logChange($deviceId, $userId, $action, $changes = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO device_change_log (device_id, user_id, action, changes)
            VALUES (:device_id, :user_id, :action, :changes)
        ");
        $stmt->execute([
            ':device_id' => $deviceId,
            ':user_id' => $userId,
            ':action' => $action,
            ':changes' => $changes
        ]);
    }
}
