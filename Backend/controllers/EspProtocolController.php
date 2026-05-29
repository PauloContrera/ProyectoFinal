<?php

namespace Controllers;

use Middleware\RateLimiter;
use Models\EspProtocol;
use PDO;

class EspProtocolController
{
    private PDO $db;
    private EspProtocol $protocolModel;
    private string $requestId;

    public function __construct($db)
    {
        $this->db = $db;
        $this->protocolModel = new EspProtocol($db);
        $this->requestId = bin2hex(random_bytes(8));
    }

    public function handle()
    {
        $payload = $this->readPayload();
        if (!$payload) return $this->protocolError(400, 'ERR_FORMATO', 'JSON mal formado', 'No se pudo interpretar el cuerpo de la solicitud.');

        if (($payload['accion'] ?? null) === 'registro') {
            return $this->register($payload);
        }

        if (isset($payload['respuesta_comando'])) {
            return $this->commandResponse($payload);
        }

        return $this->sync($payload);
    }

    public function register(?array $payload = null)
    {
        $payload = $payload ?? $this->readPayload();
        if (!$payload) return $this->protocolError(400, 'ERR_FORMATO', 'JSON mal formado', 'No se pudo interpretar el cuerpo de la solicitud.');

        RateLimiter::enforce($this->db, 'esp-register-ip', 30, 3600);

        $mac = $this->normalizeMac($payload['mac'] ?? '');
        if (!$mac || empty($payload['timestamp'])) {
            return $this->protocolError(400, 'ERR_FORMATO', 'Registro incompleto', 'Los campos mac y timestamp son obligatorios.');
        }

        $payload['mac'] = $mac;
        $payload['protocol_version'] = $payload['protocol_version'] ?? '2.0';

        if (!$this->isValidTimestamp((int)$payload['timestamp'])) {
            return $this->protocolError(400, 'ERR_TIMESTAMP', 'Timestamp invalido o fuera de rango', 'Sincronizar hora por SNTP o GET /api/esp/time antes de registrar.');
        }

        if ($this->requiresActivationKey() && !$this->isValidActivationKey($payload)) {
            return $this->protocolError(401, 'ERR_ACTIVACION', 'Clave de activacion invalida', 'Enviar palabra_clave valida para registrar el dispositivo.');
        }

        $provisionedSecret = bin2hex(random_bytes(32));
        $device = $this->protocolModel->registerDevice($payload, $provisionedSecret);
        $this->protocolModel->markConfigSent((int)$device['id'], (int)$device['config_version']);

        $response = $this->successEnvelope($device, [
            'mensaje' => 'Dispositivo registrado exitosamente',
            'config' => $this->protocolModel->buildConfig($device),
        ]);

        if (!empty($device['_provisioned_secret'])) {
            $response['provisioning'] = [
                'shared_secret' => $device['_provisioned_secret'],
                'store' => 'Guardar en NVS/Preferences del ESP32 y usarlo para firmar los siguientes paquetes.',
            ];
        }

        return $this->json(200, $response);
    }

    public function sync(?array $payload = null)
    {
        $payload = $payload ?? $this->readPayload();
        if (!$payload) return $this->protocolError(400, 'ERR_FORMATO', 'JSON mal formado', 'No se pudo interpretar el cuerpo de la solicitud.');

        $rateIdentity = $this->normalizeMac($payload['mac'] ?? '') ?: RateLimiter::clientIp();
        RateLimiter::enforce($this->db, 'esp-sync', 180, 300, $rateIdentity);

        $device = $this->authenticateDevicePayload($payload, true);
        if (!$device) return;

        if (!(bool)$device['account_enabled']) {
            return $this->protocolError(403, 'ERR_CUENTA', 'Cuenta deshabilitada temporalmente', 'Guardar lecturas en cola local y reintentar mas tarde.', $device);
        }

        $payload['data'] = $payload['data'] ?? [];
        $payload['local_alerts'] = $payload['local_alerts'] ?? [];
        if (!is_array($payload['data']) || !is_array($payload['local_alerts'])) {
            return $this->protocolError(400, 'ERR_FORMATO', 'Payload incompleto', 'Los campos data y local_alerts deben ser arrays.', $device);
        }

        if (count($payload['data']) > $this->maxBatchSize($device)) {
            return $this->protocolError(413, 'ERR_BATCH_GRANDE', 'Lote demasiado grande', 'Dividir las lecturas en lotes mas chicos.', $device);
        }

        foreach ($payload['data'] as $record) {
            if (!$this->isValidTemperatureRecord($record, $detail)) {
                return $this->protocolError(400, 'ERR_FORMATO', 'Registro de temperatura invalido', $detail, $device);
            }
        }

        foreach ($payload['local_alerts'] as $alert) {
            if (!is_array($alert)) {
                return $this->protocolError(400, 'ERR_FORMATO', 'Alerta local invalida', 'Cada alerta local debe ser un objeto.', $device);
            }
        }

        $packetId = $this->packetIdFromPayload($payload, 'sync');
        if (!$packetId) {
            return $this->protocolError(400, 'ERR_PACKET_ID', 'packet_id invalido', 'Usar 1 a 80 caracteres: letras, numeros, punto, guion, guion bajo o dos puntos.', $device);
        }

        $batch = $this->beginBatch($device, $payload, $packetId, 'sync');
        if ($batch['state'] === 'conflict') {
            return $this->protocolError(409, 'ERR_REPLAY', 'packet_id ya usado con otro contenido', 'No reutilizar packet_id para payloads distintos.', $device);
        }

        if ($batch['state'] === 'duplicate') {
            return $this->json(200, $this->successEnvelope($device, [
                'message' => 'Paquete ya recibido anteriormente',
                'cambio' => false,
                'duplicate' => true,
                'ack' => [
                    'packet_id' => $packetId,
                    'status' => 'duplicate',
                    'inserted' => (int)($batch['inserted_count'] ?? 0),
                    'duplicates' => (int)($batch['duplicate_count'] ?? 0),
                ],
            ]));
        }

        $inserted = 0;
        $duplicates = 0;
        foreach ($payload['data'] as $record) {
            if ($this->protocolModel->insertTemperatureIfMissing((int)$device['id'], (float)$record['temp'], (int)$record['time'])) {
                $inserted++;
            } else {
                $duplicates++;
            }
        }

        foreach ($payload['local_alerts'] as $alert) {
            $this->protocolModel->insertLocalAlert((int)$device['id'], $alert);
        }

        if (isset($payload['optional']) && is_array($payload['optional'])) {
            $this->protocolModel->insertDiagnostics((int)$device['id'], $payload['optional']);
        }

        $this->protocolModel->touchDeviceSync(
            (int)$device['id'],
            (int)$payload['timestamp'],
            $packetId,
            $this->sequenceFromPayload($payload)
        );

        $change = (int)$device['last_config_version_sent'] < (int)$device['config_version'];
        $this->protocolModel->finishMessageBatch((int)$batch['id'], 'accepted', $inserted, $duplicates);

        $response = $this->successEnvelope($device, [
            'message' => $inserted . ' registros insertados correctamente',
            'cambio' => $change,
            'duplicate' => false,
            'ack' => [
                'packet_id' => $packetId,
                'status' => 'accepted',
                'inserted' => $inserted,
                'duplicates' => $duplicates,
            ],
        ]);

        if ($change) {
            $response['config'] = $this->protocolModel->buildConfig($device);
            $this->protocolModel->markConfigSent((int)$device['id'], (int)$device['config_version']);
        }

        return $this->json(200, $response);
    }

    public function commandResponse(?array $payload = null)
    {
        $payload = $payload ?? $this->readPayload();
        if (!$payload) return $this->protocolError(400, 'ERR_FORMATO', 'JSON mal formado', 'No se pudo interpretar el cuerpo de la solicitud.');

        $rateIdentity = $this->normalizeMac($payload['mac'] ?? '') ?: RateLimiter::clientIp();
        RateLimiter::enforce($this->db, 'esp-command-response', 120, 300, $rateIdentity);

        $device = $this->authenticateDevicePayload($payload, true);
        if (!$device) return;

        if (!isset($payload['respuesta_comando']) || !is_array($payload['respuesta_comando'])) {
            return $this->protocolError(400, 'ERR_FORMATO', 'Respuesta de comando invalida', 'El campo respuesta_comando debe ser un objeto.', $device);
        }

        $packetId = $this->packetIdFromPayload($payload, 'command');
        if (!$packetId) {
            return $this->protocolError(400, 'ERR_PACKET_ID', 'packet_id invalido', 'Usar 1 a 80 caracteres: letras, numeros, punto, guion, guion bajo o dos puntos.', $device);
        }

        $batch = $this->beginBatch($device, $payload, $packetId, 'command');
        if ($batch['state'] === 'conflict') {
            return $this->protocolError(409, 'ERR_REPLAY', 'packet_id ya usado con otro contenido', 'No reutilizar packet_id para payloads distintos.', $device);
        }

        if ($batch['state'] !== 'duplicate') {
            $this->protocolModel->insertCommandResponse((int)$device['id'], $payload['respuesta_comando'], (int)$payload['timestamp'], $packetId);
            $this->protocolModel->finishMessageBatch((int)$batch['id'], 'accepted', 1, 0);
        }

        return $this->json(200, $this->successEnvelope($device, [
            'message' => 'Respuesta de comando recibida',
            'cambio' => false,
            'duplicate' => $batch['state'] === 'duplicate',
            'ack' => [
                'packet_id' => $packetId,
                'status' => $batch['state'] === 'duplicate' ? 'duplicate' : 'accepted',
                'inserted' => $batch['state'] === 'duplicate' ? 0 : 1,
                'duplicates' => $batch['state'] === 'duplicate' ? 1 : 0,
            ],
        ]));
    }

    public function time()
    {
        RateLimiter::enforce($this->db, 'esp-time-ip', 120, 60);

        return $this->json(200, [
            'success' => true,
            'server_time' => time(),
            'server_time_iso' => gmdate('c'),
            'timestamp_tolerance_seconds' => $this->timestampTolerance(),
            'request_id' => $this->requestId,
        ]);
    }

    private function beginBatch(array $device, array $payload, string $packetId, string $messageType): array
    {
        return $this->protocolModel->beginMessageBatch(
            (int)$device['id'],
            $packetId,
            $messageType,
            hash('sha256', $this->jsonDataForSignature($payload)),
            (int)$payload['timestamp'],
            $this->sequenceFromPayload($payload),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $payload
        );
    }

    private function authenticateDevicePayload(array &$payload, bool $requireSignature)
    {
        $mac = $this->normalizeMac($payload['mac'] ?? '');
        if (!$mac || empty($payload['timestamp'])) {
            $this->protocolError(400, 'ERR_FORMATO', 'Payload incompleto', 'Los campos mac y timestamp son obligatorios.');
            return null;
        }

        $payload['mac'] = $mac;
        $device = $this->protocolModel->findDeviceByMac($mac);
        if (!$device) {
            $this->protocolError(404, 'ERR_DISPOSITIVO', 'Dispositivo no registrado', "No existe una heladera asociada a la MAC {$mac}.");
            return null;
        }

        if (!$this->isValidTimestamp((int)$payload['timestamp'])) {
            $this->protocolError(400, 'ERR_TIMESTAMP', 'Timestamp invalido o fuera de rango', 'Sincronizar hora por SNTP o GET /api/esp/time.', $device);
            return null;
        }

        if ($requireSignature && !$this->verifySignature($payload, $device['shared_secret'] ?: $this->defaultSecret())) {
            $this->protocolError(401, 'ERR_FIRMA', 'Firma no valida o ausente', 'Recalcular HMAC_SHA256(mac + timestamp + json_data).', $device);
            return null;
        }

        return $device;
    }

    private function verifySignature(array $payload, string $secret): bool
    {
        $signature = trim((string)($payload['signature'] ?? ''));
        if ($signature === '') return false;

        $provided = strtolower($signature);
        $message = $payload['mac'] . (int)$payload['timestamp'] . $this->jsonDataForSignature($payload);
        $hex = hash_hmac('sha256', $message, $secret);
        $base64 = base64_encode(hash_hmac('sha256', $message, $secret, true));

        return hash_equals(strtolower($hex), $provided) || hash_equals($base64, $signature);
    }

    private function jsonDataForSignature(array $payload): string
    {
        unset($payload['mac'], $payload['timestamp'], $payload['signature']);
        $normalized = $this->normalizeForJson($payload);
        return json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function normalizeForJson($value)
    {
        if (!is_array($value)) return $value;

        if ($this->isList($value)) {
            return array_map(fn($item) => $this->normalizeForJson($item), $value);
        }

        ksort($value);
        foreach ($value as $key => $item) {
            $value[$key] = $this->normalizeForJson($item);
        }

        return $value;
    }

    private function isList(array $value): bool
    {
        $expected = 0;
        foreach ($value as $key => $_) {
            if ($key !== $expected++) return false;
        }
        return true;
    }

    private function isValidTimestamp(int $timestamp): bool
    {
        if ($timestamp <= 0) return false;
        return abs(time() - $timestamp) <= $this->timestampTolerance();
    }

    private function isValidTemperatureRecord($record, ?string &$detail): bool
    {
        if (!is_array($record) || !isset($record['temp'], $record['time']) || !is_numeric($record['temp']) || !is_numeric($record['time'])) {
            $detail = 'Cada registro debe incluir temp y time numericos.';
            return false;
        }

        $temperature = (float)$record['temp'];
        if ($temperature < -50 || $temperature > 80) {
            $detail = 'La temperatura debe estar entre -50 y 80 C.';
            return false;
        }

        $recordTime = (int)$record['time'];
        $now = time();
        if ($recordTime > $now + $this->recordFutureTolerance()) {
            $detail = 'La lectura tiene un timestamp futuro.';
            return false;
        }

        if ($recordTime < $now - $this->recordMaxAge()) {
            $detail = 'La lectura es demasiado antigua para aceptarse automaticamente.';
            return false;
        }

        return true;
    }

    private function packetIdFromPayload(array $payload, string $prefix): ?string
    {
        $packetId = trim((string)($payload['packet_id'] ?? ''));
        if ($packetId === '') {
            return 'legacy-' . substr(hash('sha256', $prefix . $payload['mac'] . (int)$payload['timestamp'] . $this->jsonDataForSignature($payload)), 0, 32);
        }

        if (strlen($packetId) > 80 || !preg_match('/^[A-Za-z0-9._:-]+$/', $packetId)) {
            return null;
        }

        return $packetId;
    }

    private function sequenceFromPayload(array $payload): ?int
    {
        return isset($payload['seq']) && is_numeric($payload['seq']) ? (int)$payload['seq'] : null;
    }

    private function normalizeMac(string $mac): ?string
    {
        $compact = strtoupper(preg_replace('/[^A-Fa-f0-9]/', '', $mac));
        if (strlen($compact) !== 12) return null;
        return implode(':', str_split($compact, 2));
    }

    private function readPayload(): ?array
    {
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($payload) ? $payload : null;
    }

    private function successEnvelope(array $device, array $payload): array
    {
        return array_merge([
            'success' => true,
            'server_time' => time(),
            'request_id' => $this->requestId,
            'estado_cuenta' => (bool)$device['account_enabled'],
            'palabra_clave' => $device['activation_keyword'] ?: $this->activationKeyword(),
            'config_version' => (int)($device['config_version'] ?? 1),
            'policy' => $this->policy($device),
        ], $payload);
    }

    private function protocolError(int $statusCode, string $code, string $message, string $detail, ?array $device = null)
    {
        return $this->json($statusCode, [
            'success' => false,
            'server_time' => time(),
            'request_id' => $this->requestId,
            'error' => [
                'code' => $code,
                'message' => $message,
                'detalle' => $detail,
            ],
            'retry_after_seconds' => $this->retryBaseSeconds($device),
            'estado_cuenta' => $device ? (bool)$device['account_enabled'] : false,
            'policy' => $this->policy($device),
        ]);
    }

    private function json(int $statusCode, array $payload)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        header('X-Request-ID: ' . $this->requestId);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    private function policy(?array $device): array
    {
        return [
            'max_batch_size' => $this->maxBatchSize($device),
            'retry_base_seconds' => $this->retryBaseSeconds($device),
            'timestamp_tolerance_seconds' => $this->timestampTolerance(),
            'record_max_age_seconds' => $this->recordMaxAge(),
            'record_future_tolerance_seconds' => $this->recordFutureTolerance(),
        ];
    }

    private function maxBatchSize(?array $device): int
    {
        $value = (int)($device['max_batch_size'] ?? ($_ENV['ESP_MAX_BATCH_SIZE'] ?? 120));
        return max(1, min($value, 500));
    }

    private function retryBaseSeconds(?array $device): int
    {
        $value = (int)($device['retry_base_seconds'] ?? ($_ENV['ESP_RETRY_BASE_SECONDS'] ?? 30));
        return max(5, min($value, 3600));
    }

    private function timestampTolerance(): int
    {
        return max(60, (int)($_ENV['ESP_TIMESTAMP_TOLERANCE'] ?? 900));
    }

    private function recordMaxAge(): int
    {
        return max(3600, (int)($_ENV['ESP_RECORD_MAX_AGE_SECONDS'] ?? 604800));
    }

    private function recordFutureTolerance(): int
    {
        return max(60, (int)($_ENV['ESP_RECORD_FUTURE_TOLERANCE_SECONDS'] ?? 900));
    }

    private function requiresActivationKey(): bool
    {
        return filter_var($_ENV['ESP_REQUIRE_ACTIVATION_KEY'] ?? 'true', FILTER_VALIDATE_BOOL);
    }

    private function isValidActivationKey(array $payload): bool
    {
        $provided = trim((string)($payload['palabra_clave'] ?? $payload['activation_keyword'] ?? ''));
        return $provided !== '' && hash_equals($this->activationKeyword(), $provided);
    }

    private function defaultSecret(): string
    {
        return $_ENV['ESP_DEFAULT_SECRET'] ?? 'local-dev-esp-secret';
    }

    private function activationKeyword(): string
    {
        return $_ENV['ESP_ACTIVATION_KEYWORD'] ?? 'clavesecreta4321';
    }
}
