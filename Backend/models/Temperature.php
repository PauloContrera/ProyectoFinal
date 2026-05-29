<?php

namespace Models;

use PDO;

class Temperature
{
    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function getByDeviceId(int $deviceId, int $limit = 96): array
    {
        $stmt = $this->conn->prepare("
            SELECT id, device_id, temperature, recorded_at
            FROM temperatures
            WHERE device_id = :device_id
            ORDER BY recorded_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':device_id', $deviceId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_reverse($rows);
    }
}
