<?php

namespace Models;

use PDO;

class DeviceGroup {
    private $conn;
    private $table = 'device_groups';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (name, user_id, description) VALUES (:name, :user_id, :description)");
        $stmt->execute([
            ':name' => $data['name'],
            ':user_id' => $data['user_id'],
            ':description' => $data['description']
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

    public function update($id, $data, $userId) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET name = :name, description = :description WHERE id = :id");
        $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'],
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

    private function logChange($groupId, $userId, $action, $changes = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO device_group_change_log (group_id, user_id, action, changes)
            VALUES (:group_id, :user_id, :action, :changes)
        ");
        $stmt->execute([
            ':group_id' => $groupId,
            ':user_id' => $userId,
            ':action' => $action,
            ':changes' => $changes
        ]);
    }
}
