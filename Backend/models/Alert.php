<?php

namespace Models;

use Helpers\AlertNotifier;
use PDO;

/**
 * Gestion de alertas de temperatura.
 *
 * Genera alertas cuando una lectura sale del rango configurado de la heladera,
 * con anti-spam por cooldown y respeto de ventanas de mantenimiento
 * (alert_suppression). Tambien se usa para las alertas que el firmware reporta
 * como local_alerts, deduplicandolas igual.
 */
class Alert
{
    public const TYPE_HIGH = 'TEMP_HIGH';
    public const TYPE_LOW = 'TEMP_LOW';

    private PDO $conn;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    private function cooldownSeconds(): int
    {
        // Ventana minima entre alertas del mismo tipo para la misma heladera.
        return max(60, (int)($_ENV['ALERT_COOLDOWN_SECONDS'] ?? 1800));
    }

    /**
     * Evalua una lectura contra el rango [min, max] de la heladera y crea una
     * alerta si corresponde. Devuelve el tipo creado (TEMP_HIGH/TEMP_LOW) o null.
     */
    public function evaluateReading(int $deviceId, float $temperature, ?float $min, ?float $max, int $recordedAt): ?string
    {
        $type = null;
        if ($max !== null && $temperature > $max) {
            $type = self::TYPE_HIGH;
        } elseif ($min !== null && $temperature < $min) {
            $type = self::TYPE_LOW;
        }

        if ($type === null) {
            return null;
        }

        return $this->raise($deviceId, $type, $temperature, $recordedAt);
    }

    /**
     * Registra una alerta proveniente de un local_alert del firmware, con la
     * misma deduplicacion que la evaluacion server-side.
     */
    public function fromLocalAlert(int $deviceId, string $rawType, ?float $temperature, int $recordedAt): ?string
    {
        $type = in_array(strtolower($rawType), ['temp_low'], true)
            ? self::TYPE_LOW
            : self::TYPE_HIGH;

        return $this->raise($deviceId, $type, $temperature, $recordedAt);
    }

    /**
     * Crea la alerta si no esta suprimida ni en cooldown. Devuelve el tipo o null.
     */
    private function raise(int $deviceId, string $type, ?float $temperature, int $recordedAt): ?string
    {
        if ($this->isSuppressed($deviceId)) {
            return null;
        }

        if ($this->hasRecentUnresolved($deviceId, $type, $recordedAt)) {
            return null;
        }

        $recordedAtSql = gmdate('Y-m-d H:i:s', $recordedAt);
        $stmt = $this->conn->prepare("
            INSERT INTO alerts (device_id, temperature, recorded_at, type, notified, acknowledged, resolved)
            VALUES (:device_id, :temperature, :recorded_at, :type, 0, 0, 0)
        ");
        $stmt->execute([
            ':device_id' => $deviceId,
            ':temperature' => $temperature,
            ':recorded_at' => $recordedAtSql,
            ':type' => $type,
        ]);
        $alertId = (int)$this->conn->lastInsertId();

        // Notificacion por email al dueno (el SMS lo gestiona el ESP).
        // Tolerante a fallos: nunca interrumpe el flujo de sync.
        $notified = AlertNotifier::notify($this->conn, $deviceId, $type, $temperature, $recordedAtSql);
        if ($notified) {
            $this->conn->prepare("UPDATE alerts SET notified = 1 WHERE id = :id")
                ->execute([':id' => $alertId]);
        }

        return $type;
    }

    /**
     * Hay una ventana de mantenimiento activa para la heladera ahora mismo?
     */
    public function isSuppressed(int $deviceId): bool
    {
        $stmt = $this->conn->prepare("
            SELECT id
            FROM alert_suppression
            WHERE device_id = :device_id
              AND start_at <= NOW()
              AND (end_at IS NULL OR end_at >= NOW())
            LIMIT 1
        ");
        $stmt->execute([':device_id' => $deviceId]);
        return (bool)$stmt->fetch();
    }

    /**
     * Ya hay una alerta sin resolver del mismo tipo dentro del cooldown?
     */
    private function hasRecentUnresolved(int $deviceId, string $type, int $recordedAt): bool
    {
        $cutoff = gmdate('Y-m-d H:i:s', $recordedAt - $this->cooldownSeconds());
        $stmt = $this->conn->prepare("
            SELECT id
            FROM alerts
            WHERE device_id = :device_id
              AND type = :type
              AND resolved = 0
              AND recorded_at >= :cutoff
            ORDER BY recorded_at DESC
            LIMIT 1
        ");
        $stmt->execute([
            ':device_id' => $deviceId,
            ':type' => $type,
            ':cutoff' => $cutoff,
        ]);
        return (bool)$stmt->fetch();
    }

    public function getByDevice(int $deviceId, int $limit = 50): array
    {
        $stmt = $this->conn->prepare("
            SELECT id, device_id, temperature, recorded_at, type, notified,
                   acknowledged, acknowledged_at, resolved, resolved_at
            FROM alerts
            WHERE device_id = :device_id
            ORDER BY recorded_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':device_id', $deviceId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Alarmero: alertas de todas las heladeras accesibles para el usuario.
     * - admin/superadmin: todas.
     * - client: las de sus heladeras.
     * - visitor: las de heladeras compartidas (device_access).
     * Filtro opcional $status: 'active' | 'acknowledged' | 'resolved' | 'open'.
     */
    public function getForUser(int $userId, string $role, string $status = 'open', int $limit = 200): array
    {
        $params = [];
        $where = [];

        if (in_array($role, ['admin', 'superadmin'], true)) {
            // sin restriccion por dueno
        } elseif ($role === 'visitor') {
            $where[] = 'd.id IN (SELECT device_id FROM device_access WHERE user_id = :uid)';
            $params[':uid'] = $userId;
        } else {
            $where[] = 'd.user_id = :uid';
            $params[':uid'] = $userId;
        }

        if ($status === 'active') {
            $where[] = 'a.resolved = 0 AND a.acknowledged = 0';
        } elseif ($status === 'acknowledged') {
            $where[] = 'a.resolved = 0 AND a.acknowledged = 1';
        } elseif ($status === 'resolved') {
            $where[] = 'a.resolved = 1';
        } elseif ($status === 'open') {
            $where[] = 'a.resolved = 0';
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "
            SELECT a.id, a.device_id, a.temperature, a.recorded_at, a.type,
                   a.notified, a.acknowledged, a.acknowledged_at, a.resolved, a.resolved_at,
                   d.name AS device_name, d.location AS device_location
            FROM alerts a
            JOIN devices d ON d.id = a.device_id
            {$whereSql}
            ORDER BY a.resolved ASC, a.acknowledged ASC, a.recorded_at DESC
            LIMIT :limit
        ";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function acknowledge(int $alertId): bool
    {
        $stmt = $this->conn->prepare("
            UPDATE alerts
            SET acknowledged = 1, acknowledged_at = NOW()
            WHERE id = :id AND acknowledged = 0 AND resolved = 0
        ");
        $stmt->execute([':id' => $alertId]);
        return $stmt->rowCount() > 0;
    }

    public function getById(int $alertId): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM alerts WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $alertId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function resolve(int $alertId): bool
    {
        $stmt = $this->conn->prepare("
            UPDATE alerts
            SET resolved = 1, resolved_at = NOW()
            WHERE id = :id AND resolved = 0
        ");
        $stmt->execute([':id' => $alertId]);
        return $stmt->rowCount() > 0;
    }
}
