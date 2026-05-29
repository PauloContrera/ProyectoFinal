<?php

declare(strict_types=1);

const TEST_MAC = 'AA:BB:CC:DD:EE:99';
const TEST_DEVICE_CODE = 'ESP-AABBCCDDEE99';

$backendDir = dirname(__DIR__);
$env = loadEnv($backendDir . DIRECTORY_SEPARATOR . '.env');
$baseUrl = rtrim(getenv('ESP_TEST_BASE_URL') ?: 'http://localhost:8000/api', '/');
$secret = $env['ESP_DEFAULT_SECRET'] ?? 'local-dev-esp-secret';
$activationKeyword = $env['ESP_ACTIVATION_KEYWORD'] ?? 'clavesecreta4321';
$pdo = connectDb($env);

cleanup($pdo);

try {
    $now = time();

    $registerPayload = [
        'accion' => 'registro',
        'mac' => TEST_MAC,
        'modelo' => 'ESP32-GSM-V3',
        'firmware_version' => '1.0.2',
        'protocol_version' => '2.0',
        'sim_imei' => '354829071122334',
        'timestamp' => $now,
        'palabra_clave' => $activationKeyword,
    ];

    $registerWithoutKeyword = $registerPayload;
    unset($registerWithoutKeyword['palabra_clave']);
    $activationError = postJson($baseUrl . '/esp/register', $registerWithoutKeyword);
    assertTrue($activationError['status'] === 401, 'Registro sin palabra_clave HTTP 401');
    assertTrue(($activationError['json']['error']['code'] ?? '') === 'ERR_ACTIVACION', 'Registro sin palabra_clave devuelve ERR_ACTIVACION');
    assertTrue(!isset($activationError['json']['palabra_clave']), 'Registro rechazado no filtra palabra_clave');

    $register = postJson($baseUrl . '/esp/register', $registerPayload);
    assertTrue($register['status'] === 200, 'Registro HTTP 200');
    assertTrue(($register['json']['success'] ?? false) === true, 'Registro success=true');
    assertTrue(isset($register['json']['config']), 'Registro devuelve config');
    assertTrue(!empty($register['json']['provisioning']['shared_secret']), 'Registro provisiona shared_secret');
    $secret = $register['json']['provisioning']['shared_secret'];

    $deviceId = (int)$pdo->query("SELECT id FROM devices WHERE mac_address = " . $pdo->quote(TEST_MAC))->fetchColumn();
    assertTrue($deviceId > 0, 'Dispositivo creado en DB');

    $syncPayload = [
        'mac' => TEST_MAC,
        'timestamp' => $now + 1,
        'packet_id' => 'sync-' . $now,
        'seq' => 1,
        'data' => [
            ['temp' => 4.3, 'time' => $now - 60],
            ['temp' => 4.6, 'time' => $now - 30],
        ],
        'local_alerts' => [
            ['type' => 'temp_high', 'temp' => 10.1, 'time' => $now - 20],
            ['type' => 'power_outage', 'time' => $now - 10],
        ],
        'optional' => [
            'uptime' => 86400,
            'signal_strength' => -67,
            'battery_level' => 89,
        ],
    ];
    $syncPayload['signature'] = signPayload($syncPayload, $secret);

    $sync = postJson($baseUrl . '/esp/sync', $syncPayload);
    assertTrue($sync['status'] === 200, 'Sync HTTP 200');
    assertTrue(($sync['json']['success'] ?? false) === true, 'Sync success=true');
    assertTrue(($sync['json']['estado_cuenta'] ?? false) === true, 'Sync estado_cuenta=true');
    assertTrue(($sync['json']['ack']['packet_id'] ?? '') === 'sync-' . $now, 'Sync devuelve ACK packet_id');
    assertTrue(isset($sync['json']['server_time']), 'Sync devuelve server_time');

    $syncDuplicate = postJson($baseUrl . '/esp/sync', $syncPayload);
    assertTrue($syncDuplicate['status'] === 200, 'Sync duplicado HTTP 200');
    assertTrue(($syncDuplicate['json']['duplicate'] ?? false) === true, 'Sync duplicado queda idempotente');

    $badSignaturePayload = $syncPayload;
    $badSignaturePayload['timestamp'] = $now + 2;
    $badSignaturePayload['packet_id'] = 'sync-bad-' . $now;
    $badSignaturePayload['signature'] = 'firma-invalida';
    $badSignature = postJson($baseUrl . '/esp/sync', $badSignaturePayload);
    assertTrue($badSignature['status'] === 401, 'Firma inválida HTTP 401');
    assertTrue(($badSignature['json']['error']['code'] ?? '') === 'ERR_FIRMA', 'Firma inválida devuelve ERR_FIRMA');

    assertTrue(!isset($badSignature['json']['palabra_clave']), 'Errores ESP no filtran palabra_clave');

    $commandPayload = [
        'mac' => TEST_MAC,
        'timestamp' => $now + 3,
        'packet_id' => 'cmd-' . $now,
        'seq' => 2,
        'respuesta_comando' => [
            'tipo' => 'cambio_config',
            'estado' => 'ok',
            'detalle' => 'Parámetros aplicados correctamente',
        ],
    ];
    $commandPayload['signature'] = signPayload($commandPayload, $secret);

    $command = postJson($baseUrl . '/esp/command-response', $commandPayload);
    assertTrue($command['status'] === 200, 'Respuesta comando HTTP 200');
    assertTrue(($command['json']['success'] ?? false) === true, 'Respuesta comando success=true');

    assertDbCount($pdo, 'temperatures', $deviceId, 2);
    assertDbCount($pdo, 'esp_local_alerts', $deviceId, 2);
    assertDbCount($pdo, 'esp_diagnostics', $deviceId, 1);
    assertDbCount($pdo, 'esp_command_responses', $deviceId, 1);
    assertDbCount($pdo, 'esp_sync_batches', $deviceId, 2);

    echo "OK protocolo HTTP/SMS verificado por HTTP y DB\n";
} finally {
    cleanup($pdo);
}

function loadEnv(string $path): array
{
    $env = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
    return $env;
}

function connectDb(array $env): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        $env['DB_HOST'] ?? 'localhost',
        $env['DB_NAME'] ?? 'temp_segura'
    );

    return new PDO($dsn, $env['DB_USER'] ?? 'root', $env['DB_PASS'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function postJson(string $url, array $payload): array
{
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $body,
            'ignore_errors' => true,
            'timeout' => 10,
        ],
    ]);

    $raw = @file_get_contents($url, false, $context);
    $status = 0;
    foreach ($http_response_header ?? [] as $header) {
        if (preg_match('#^HTTP/\S+\s+(\d+)#', $header, $matches)) {
            $status = (int)$matches[1];
            break;
        }
    }

    if ($raw === false) {
        throw new RuntimeException("No se pudo conectar a {$url}. Verifica que el backend esté corriendo.");
    }

    return [
        'status' => $status,
        'json' => json_decode($raw, true) ?: [],
        'raw' => $raw,
    ];
}

function signPayload(array $payload, string $secret): string
{
    $mac = normalizeMac($payload['mac']);
    $timestamp = (int)$payload['timestamp'];
    unset($payload['mac'], $payload['timestamp'], $payload['signature']);

    return hash_hmac(
        'sha256',
        $mac . $timestamp . json_encode(normalizeForJson($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        $secret
    );
}

function normalizeMac(string $mac): string
{
    $compact = strtoupper(preg_replace('/[^A-Fa-f0-9]/', '', $mac));
    return implode(':', str_split($compact, 2));
}

function normalizeForJson(mixed $value): mixed
{
    if (!is_array($value)) return $value;

    if (array_keys($value) === range(0, count($value) - 1)) {
        return array_map('normalizeForJson', $value);
    }

    ksort($value);
    foreach ($value as $key => $item) {
        $value[$key] = normalizeForJson($item);
    }

    return $value;
}

function assertDbCount(PDO $pdo, string $table, int $deviceId, int $expected): void
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE device_id = ?");
    $stmt->execute([$deviceId]);
    assertTrue((int)$stmt->fetchColumn() === $expected, "{$table} tiene {$expected} registros");
}

function assertTrue(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException("Fallo: {$label}");
    }
    echo "OK - {$label}\n";
}

function cleanup(PDO $pdo): void
{
    $pdo->exec("DELETE FROM rate_limit_events WHERE bucket LIKE 'esp-%'");

    $stmt = $pdo->prepare("DELETE FROM devices WHERE mac_address = :mac OR device_code = :device_code");
    $stmt->execute([
        ':mac' => TEST_MAC,
        ':device_code' => TEST_DEVICE_CODE,
    ]);
}
