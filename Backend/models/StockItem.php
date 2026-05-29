<?php

namespace Models;

use PDO;

class StockItem
{
    private PDO $conn;
    private string $table = 'stock_items';

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getByDeviceId(int $deviceId): array
    {
        $stmt = $this->conn->prepare("
            SELECT id, device_id, name, quantity, expiration_date, created_at, updated_at
            FROM {$this->table}
            WHERE device_id = :device_id
            ORDER BY expiration_date IS NULL, expiration_date ASC, name ASC
        ");
        $stmt->execute([':device_id' => $deviceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id)
    {
        $stmt = $this->conn->prepare("
            SELECT id, device_id, name, quantity, expiration_date, created_at, updated_at
            FROM {$this->table}
            WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(int $deviceId, array $data, ?int $userId = null): int
    {
        $stmt = $this->conn->prepare("
            INSERT INTO {$this->table} (device_id, name, quantity, expiration_date)
            VALUES (:device_id, :name, :quantity, :expiration_date)
        ");
        $stmt->execute([
            ':device_id' => $deviceId,
            ':name' => $data['name'],
            ':quantity' => $data['quantity'],
            ':expiration_date' => $data['expiration_date'],
        ]);

        $stockId = (int)$this->conn->lastInsertId();
        $this->logFullChange($stockId, $deviceId, $userId, 'create', $data);

        return $stockId;
    }

    public function update(int $id, array $data, ?int $userId = null): int
    {
        $current = $this->getById($id);
        if (!$current) return 0;

        $stmt = $this->conn->prepare("
            UPDATE {$this->table}
            SET name = :name,
                quantity = :quantity,
                expiration_date = :expiration_date,
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':name' => $data['name'],
            ':quantity' => $data['quantity'],
            ':expiration_date' => $data['expiration_date'],
            ':id' => $id,
        ]);

        $this->logDifferences($id, (int)$current['device_id'], $userId, $current, $data);

        return $stmt->rowCount();
    }

    public function delete(int $id, ?int $userId = null): int
    {
        $current = $this->getById($id);
        if (!$current) return 0;

        $this->logFullChange($id, (int)$current['device_id'], $userId, 'delete', $current);

        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount();
    }

    private function logDifferences(int $stockId, int $deviceId, ?int $userId, array $old, array $new): void
    {
        foreach (['name', 'quantity', 'expiration_date'] as $field) {
            $oldValue = $old[$field] ?? null;
            $newValue = $new[$field] ?? null;

            if ((string)$oldValue !== (string)$newValue) {
                $this->insertLog($stockId, $deviceId, $userId, 'update', $field, $oldValue, $newValue);
            }
        }
    }

    private function logFullChange(int $stockId, int $deviceId, ?int $userId, string $action, array $data): void
    {
        foreach (['name', 'quantity', 'expiration_date'] as $field) {
            $value = $data[$field] ?? null;
            if ($action === 'create') {
                $this->insertLog($stockId, $deviceId, $userId, $action, $field, null, $value);
            } elseif ($action === 'delete') {
                $this->insertLog($stockId, $deviceId, $userId, $action, $field, $value, null);
            }
        }
    }

    private function insertLog(int $stockId, int $deviceId, ?int $userId, string $action, string $field, $oldValue, $newValue): void
    {
        $stmt = $this->conn->prepare("
            INSERT INTO stock_item_change_log (
                stock_item_id, device_id, user_id, action, field_changed, old_value, new_value
            ) VALUES (
                :stock_item_id, :device_id, :user_id, :action, :field_changed, :old_value, :new_value
            )
        ");
        $stmt->execute([
            ':stock_item_id' => $stockId,
            ':device_id' => $deviceId,
            ':user_id' => $userId,
            ':action' => $action,
            ':field_changed' => $field,
            ':old_value' => $oldValue,
            ':new_value' => $newValue,
        ]);
    }
}
