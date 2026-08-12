<?php

namespace Models;

use PDO;
use PDOException;

class EspProtocol
{
    private PDO $conn;
    private Alert $alertModel;
    private StockMovement $movementModel;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
        $this->alertModel = new Alert($db);
        $this->movementModel = new StockMovement($db);
    }

    /**
     * Procesa las lecturas RFID de un sync y devuelve un resumen por lectura,
     * para que el ESP pueda confirmar que cada tarjeta quedo registrada.
     *
     * Nunca lanza: una tarjeta mal formada se reporta como invalida y el resto
     * del paquete (temperaturas, alertas) sigue su curso.
     */
    public function processRfidEvents(array $device, array $events, ?string $packetId): array
    {
        $deviceId = (int)$device['id'];
        $results = [];

        foreach ($events as $index => $event) {
            if (!is_array($event)) {
                $results[] = ['index' => $index, 'status' => 'invalid', 'reason' => 'Cada lectura debe ser un objeto'];
                continue;
            }

            $uid = StockMovement::normalizeUid($event['uid'] ?? $event['rfid'] ?? '');
            if ($uid === null) {
                $results[] = ['index' => $index, 'status' => 'invalid', 'reason' => 'UID vacio o con caracteres no permitidos'];
                continue;
            }

            $direction = StockMovement::normalizeDirection($event['movimiento'] ?? $event['direction'] ?? $event['tipo'] ?? null);
            if ($direction === null) {
                $results[] = ['index' => $index, 'uid' => $uid, 'status' => 'invalid', 'reason' => 'movimiento debe ser carga/descarga (o 1/2)'];
                continue;
            }

            $quantity = isset($event['cantidad']) ? (int)$event['cantidad'] : (isset($event['quantity']) ? (int)$event['quantity'] : 1);
            if ($quantity < 1 || $quantity > 10000) {
                $results[] = ['index' => $index, 'uid' => $uid, 'status' => 'invalid', 'reason' => 'cantidad fuera de rango (1 a 10000)'];
                continue;
            }

            $occurredAt = isset($event['time']) && is_numeric($event['time']) ? (int)$event['time'] : time();

            $outcome = $this->movementModel->apply($deviceId, $uid, $direction, $quantity, $occurredAt, $packetId);
            $results[] = array_merge(['index' => $index, 'uid' => $uid, 'direction' => $direction], $outcome);
        }

        return $results;
    }

    /**
     * Evalua una lectura aceptada contra el rango configurado de la heladera y
     * genera una alerta server-side si corresponde (con dedup y suppression).
     */
    public function evaluateReadingAlert(array $device, float $temperature, int $recordedAt): ?string
    {
        $min = isset($device['min_temp']) ? (float)$device['min_temp'] : null;
        $max = isset($device['max_temp']) ? (float)$device['max_temp'] : null;

        return $this->alertModel->evaluateReading((int)$device['id'], $temperature, $min, $max, $recordedAt);
    }

    /**
     * Devuelve null (y no false) cuando la MAC no existe, para poder tipar los
     * consumidores como ?array.
     */
    public function findDeviceByMac(string $mac): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT d.*, dg.name AS group_name
            FROM devices d
            LEFT JOIN device_groups dg ON dg.id = d.group_id
            WHERE d.mac_address = :mac
            LIMIT 1
        ");
        $stmt->execute([':mac' => $mac]);
        $device = $stmt->fetch(PDO::FETCH_ASSOC);

        return $device ?: null;
    }

    public function registerDevice(array $payload, string $provisionedSecret): array
    {
        $mac = $payload['mac'];
        $device = $this->findDeviceByMac($mac);

        if ($device) {
            // Heladera preprovisionada desde administracion o ya registrada: se
            // actualizan los datos de firmware sin tocar el secreto existente.
            // Las filas viejas sin secreto (seeds, altas manuales por SQL) reciben
            // uno ahora, porque el sync exige firma con secreto propio.
            $needsSecret = trim((string)($device['shared_secret'] ?? '')) === '';

            $stmt = $this->conn->prepare("
                UPDATE devices
                SET registered_model = :model,
                    firmware_version = COALESCE(:firmware_version, firmware_version),
                    sim_imei = :sim_imei,
                    protocol_version = COALESCE(:protocol_version, protocol_version),
                    shared_secret = COALESCE(NULLIF(shared_secret, ''), :shared_secret)
                WHERE id = :id
            ");
            $stmt->execute([
                ':model' => $payload['modelo'] ?? null,
                ':firmware_version' => $payload['firmware_version'] ?? null,
                ':sim_imei' => $payload['sim_imei'] ?? null,
                ':protocol_version' => $payload['protocol_version'] ?? null,
                ':shared_secret' => $provisionedSecret,
                ':id' => $device['id'],
            ]);

            $updated = $this->findDeviceByMac($mac);
            $updated['_created'] = false;
            if ($needsSecret) {
                $updated['_provisioned_secret'] = $provisionedSecret;
            }
            return $updated;
        }

        $deviceCode = $this->deviceCodeFromMac($mac);
        $name = 'ESP ' . $mac;

        $stmt = $this->conn->prepare("
            INSERT INTO devices (
                device_code,
                mac_address,
                shared_secret,
                name,
                location,
                min_temp,
                max_temp,
                firmware_version,
                registered_model,
                sim_imei,
                protocol_version,
                account_enabled,
                activation_keyword,
                send_interval_seconds,
                config_version
            ) VALUES (
                :device_code,
                :mac_address,
                :shared_secret,
                :name,
                :location,
                2,
                8,
                :firmware_version,
                :registered_model,
                :sim_imei,
                :protocol_version,
                1,
                :activation_keyword,
                900,
                1
            )
        ");
        $stmt->execute([
            ':device_code' => $deviceCode,
            ':mac_address' => $mac,
            ':shared_secret' => $provisionedSecret,
            ':name' => $name,
            ':location' => 'Pendiente de asignar',
            ':firmware_version' => $payload['firmware_version'] ?? null,
            ':registered_model' => $payload['modelo'] ?? null,
            ':sim_imei' => $payload['sim_imei'] ?? null,
            ':protocol_version' => $payload['protocol_version'] ?? null,
            ':activation_keyword' => $this->defaultActivationKeyword(),
        ]);

        $created = $this->findDeviceByMac($mac);
        $created['_created'] = true;
        $created['_provisioned_secret'] = $provisionedSecret;

        return $created;
    }

    public function beginMessageBatch(
        int $deviceId,
        string $packetId,
        string $messageType,
        string $requestHash,
        int $packetTimestamp,
        ?int $sequenceNumber,
        string $ipAddress,
        array $payload
    ): array {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO esp_sync_batches (
                    device_id,
                    packet_id,
                    message_type,
                    request_hash,
                    packet_timestamp,
                    sequence_number,
                    ip_address,
                    payload_json
                ) VALUES (
                    :device_id,
                    :packet_id,
                    :message_type,
                    :request_hash,
                    :packet_timestamp,
                    :sequence_number,
                    :ip_address,
                    :payload_json
                )
            ");
            $stmt->execute([
                ':device_id' => $deviceId,
                ':packet_id' => $packetId,
                ':message_type' => $messageType,
                ':request_hash' => $requestHash,
                ':packet_timestamp' => $this->toSqlDate($packetTimestamp),
                ':sequence_number' => $sequenceNumber,
                ':ip_address' => $ipAddress,
                ':payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

            return [
                'state' => 'new',
                'id' => (int)$this->conn->lastInsertId(),
            ];
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }

            $stmt = $this->conn->prepare("
                SELECT id, request_hash, status, inserted_count, duplicate_count
                FROM esp_sync_batches
                WHERE device_id = :device_id AND packet_id = :packet_id
                LIMIT 1
            ");
            $stmt->execute([
                ':device_id' => $deviceId,
                ':packet_id' => $packetId,
            ]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing && hash_equals((string)$existing['request_hash'], $requestHash)) {
                return [
                    'state' => 'duplicate',
                    'id' => (int)$existing['id'],
                    'inserted_count' => (int)$existing['inserted_count'],
                    'duplicate_count' => (int)$existing['duplicate_count'],
                    'status' => $existing['status'],
                ];
            }

            return [
                'state' => 'conflict',
                'id' => $existing ? (int)$existing['id'] : null,
            ];
        }
    }

    public function finishMessageBatch(int $batchId, string $status, int $insertedCount, int $duplicateCount = 0): void
    {
        $stmt = $this->conn->prepare("
            UPDATE esp_sync_batches
            SET status = :status,
                inserted_count = :inserted_count,
                duplicate_count = :duplicate_count
            WHERE id = :id
        ");
        $stmt->execute([
            ':status' => $status,
            ':inserted_count' => $insertedCount,
            ':duplicate_count' => $duplicateCount,
            ':id' => $batchId,
        ]);
    }

    public function insertTemperatureIfMissing(int $deviceId, float $temperature, int $recordedAt): bool
    {
        $recordedAtSql = $this->toSqlDate($recordedAt);
        $exists = $this->conn->prepare("
            SELECT id
            FROM temperatures
            WHERE device_id = :device_id AND recorded_at = :recorded_at
            LIMIT 1
        ");
        $exists->execute([
            ':device_id' => $deviceId,
            ':recorded_at' => $recordedAtSql,
        ]);

        if ($exists->fetch()) return false;

        $stmt = $this->conn->prepare("
            INSERT INTO temperatures (device_id, temperature, recorded_at)
            VALUES (:device_id, :temperature, :recorded_at)
        ");
        $stmt->execute([
            ':device_id' => $deviceId,
            ':temperature' => $temperature,
            ':recorded_at' => $recordedAtSql,
        ]);

        return true;
    }

    public function insertLocalAlert(int $deviceId, array $alert): void
    {
        $type = trim((string)($alert['type'] ?? 'unknown'));
        $occurredAt = (int)($alert['time'] ?? time());

        $stmt = $this->conn->prepare("
            INSERT INTO esp_local_alerts (device_id, alert_type, temperature, occurred_at, payload_json)
            VALUES (:device_id, :alert_type, :temperature, :occurred_at, :payload_json)
        ");
        $stmt->execute([
            ':device_id' => $deviceId,
            ':alert_type' => $type,
            ':temperature' => isset($alert['temp']) ? (float)$alert['temp'] : null,
            ':occurred_at' => $this->toSqlDate($occurredAt),
            ':payload_json' => json_encode($alert, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        if (in_array(strtolower($type), ['temp_high', 'temp_low'], true)) {
            $temperature = isset($alert['temp']) ? (float)$alert['temp'] : null;
            $this->alertModel->fromLocalAlert($deviceId, $type, $temperature, $occurredAt);
        }
    }

    public function insertDiagnostics(int $deviceId, array $optional): void
    {
        $stmt = $this->conn->prepare("
            INSERT INTO esp_diagnostics (
                device_id,
                uptime,
                signal_strength,
                battery_level,
                payload_json
            ) VALUES (
                :device_id,
                :uptime,
                :signal_strength,
                :battery_level,
                :payload_json
            )
        ");
        $stmt->execute([
            ':device_id' => $deviceId,
            ':uptime' => isset($optional['uptime']) ? (int)$optional['uptime'] : null,
            ':signal_strength' => isset($optional['signal_strength']) ? (int)$optional['signal_strength'] : null,
            ':battery_level' => isset($optional['battery_level']) ? (int)$optional['battery_level'] : null,
            ':payload_json' => json_encode($optional, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function insertCommandResponse(int $deviceId, array $response, int $timestamp, ?string $packetId): void
    {
        $stmt = $this->conn->prepare("
            INSERT INTO esp_command_responses (
                device_id,
                packet_id,
                command_type,
                status,
                detail,
                command_time,
                payload_json
            ) VALUES (
                :device_id,
                :packet_id,
                :command_type,
                :status,
                :detail,
                :command_time,
                :payload_json
            )
        ");
        $stmt->execute([
            ':device_id' => $deviceId,
            ':packet_id' => $packetId,
            ':command_type' => $response['tipo'] ?? 'unknown',
            ':status' => $response['estado'] ?? 'unknown',
            ':detail' => $response['detalle'] ?? null,
            ':command_time' => $this->toSqlDate($timestamp),
            ':payload_json' => json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function touchDeviceSync(int $deviceId, int $deviceTimestamp, ?string $packetId = null, ?int $sequenceNumber = null): void
    {
        $now = time();
        $stmt = $this->conn->prepare("
            UPDATE devices
            SET last_reported_at = NOW(),
                last_sync_at = NOW(),
                device_time = :device_time,
                time_discrepancy = :time_discrepancy,
                last_sequence = COALESCE(:sequence_number, last_sequence),
                last_packet_id = COALESCE(:packet_id, last_packet_id),
                last_packet_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':device_time' => $this->toSqlDate($deviceTimestamp),
            ':time_discrepancy' => abs($now - $deviceTimestamp),
            ':sequence_number' => $sequenceNumber,
            ':packet_id' => $packetId,
            ':id' => $deviceId,
        ]);
    }

    public function markConfigSent(int $deviceId, int $version): void
    {
        $stmt = $this->conn->prepare("
            UPDATE devices
            SET last_config_version_sent = :version
            WHERE id = :id
        ");
        $stmt->execute([
            ':version' => $version,
            ':id' => $deviceId,
        ]);
    }

    public function buildConfig(array $device): array
    {
        $phones = [];
        if (!empty($device['sms_phones'])) {
            $decoded = json_decode($device['sms_phones'], true);
            $phones = is_array($decoded) ? array_values($decoded) : [];
        }

        return [
            'temp_min' => (float)$device['min_temp'],
            'temp_max' => (float)$device['max_temp'],
            'grupo' => $device['group_name'] ?? '',
            'area' => $device['name'] ?? '',
            'telefonos' => $phones,
            'ubicacion' => $device['location'] ?? '',
            'tiempo_espera' => (int)($device['send_interval_seconds'] ?? 900),
            'url_backup' => $device['backup_url'] ?? '',
            'config_version' => (int)($device['config_version'] ?? 1),
            'protocol_version' => $device['protocol_version'] ?: '2.0',
            'max_batch_size' => (int)($device['max_batch_size'] ?? 120),
            'retry_base_seconds' => (int)($device['retry_base_seconds'] ?? 30),
        ];
    }

    private function defaultActivationKeyword(): string
    {
        return $_ENV['ESP_ACTIVATION_KEYWORD'] ?? 'clavesecreta4321';
    }

    private function deviceCodeFromMac(string $mac): string
    {
        $compact = preg_replace('/[^A-Fa-f0-9]/', '', $mac);
        return 'ESP-' . strtoupper(substr($compact, 0, 24));
    }

    private function toSqlDate(int $timestamp): string
    {
        return gmdate('Y-m-d H:i:s', $timestamp);
    }
}
