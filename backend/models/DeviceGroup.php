<?php

namespace Models;

use PDO;

class DeviceGroup
{
    private $conn;
    private $table = 'device_groups';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create($data)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO {$this->table} (name, user_id, description)
            VALUES (:name, :user_id, :description)
        ");
        $stmt->execute([
            ':name' => $data['name'],
            ':user_id' => $data['user_id'],
            ':description' => $data['description']
        ]);
        $id = $this->conn->lastInsertId();
        $this->logFullChange($id, $data['user_id'], 'create', $data);
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

    public function update($id, $data, $userId)
    {
        $current = $this->getById($id);
        if (!$current) return 0;

        $stmt = $this->conn->prepare("
            UPDATE {$this->table}
            SET name = :name, description = :description
            WHERE id = :id
        ");
        $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'],
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

    private function logDifferences($groupId, $userId, $old, $new)
    {
        $campos = ['name', 'description'];
        foreach ($campos as $campo) {
            $oldValue = $old[$campo];
            $newValue = $new[$campo] ?? null;
            if ($oldValue != $newValue) {
                $this->insertLog($groupId, $userId, 'update', $campo, $oldValue, $newValue);
            }
        }
    }

    private function logFullChange($groupId, $userId, $action, $data)
    {
        foreach (['name', 'description'] as $campo) {
            $value = $data[$campo] ?? null;
            if ($action === 'create') {
                $this->insertLog($groupId, $userId, $action, $campo, null, $value);
            } elseif ($action === 'delete') {
                $this->insertLog($groupId, $userId, $action, $campo, $value, null);
            }
        }
    }
    public function hasDevices($groupId)
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM devices WHERE group_id = ?");
        $stmt->execute([$groupId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['total'] > 0;
    }

    private function insertLog($groupId, $userId, $action, $field, $oldValue, $newValue)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO device_group_change_log (group_id, user_id, action, field_changed, old_value, new_value)
            VALUES (:group_id, :user_id, :action, :field_changed, :old_value, :new_value)
        ");
        $stmt->execute([
            ':group_id' => $groupId,
            ':user_id' => $userId,
            ':action' => $action,
            ':field_changed' => $field,
            ':old_value' => $oldValue,
            ':new_value' => $newValue
        ]);
    }
}
