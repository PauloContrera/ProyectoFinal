<?php

declare(strict_types=1);

const TEST_PREFIX = 'flowtest_';
const TEST_PASSWORD = 'FlowTest123';

$backendDir = dirname(__DIR__);
$env = loadEnv($backendDir . DIRECTORY_SEPARATOR . '.env');
$baseUrl = rtrim(getenv('BACKEND_TEST_BASE_URL') ?: 'http://127.0.0.1:8000/api', '/');
$pdo = connectDb($env);
$exitCode = 0;

try {
    cleanupTestData($pdo);
    runBackendRoleFlow($pdo, $baseUrl);
    echo "\nOK flujo integral de usuarios, permisos, stock, temperaturas y auditoria verificado.\n";
} catch (Throwable $exception) {
    $exitCode = 1;
    fwrite(STDERR, "\nERROR en backend_roles_flow_test.php\n");
    fwrite(STDERR, $exception->getMessage() . "\n");
} finally {
    try {
        cleanupTestData($pdo);
    } catch (Throwable $cleanupError) {
        $exitCode = 1;
        fwrite(STDERR, "ERROR limpiando datos de prueba: " . $cleanupError->getMessage() . "\n");
    }
}

exit($exitCode);

function runBackendRoleFlow(PDO $pdo, string $baseUrl): void
{
    $suffix = strtolower(date('His') . bin2hex(random_bytes(2)));

    $superadmin = createVerifiedUser($pdo, "super_{$suffix}", 'superadmin');
    $admin = createVerifiedUser($pdo, "admin_{$suffix}", 'admin');
    $client = createVerifiedUser($pdo, "client_{$suffix}", 'client');
    $visitor = createVerifiedUser($pdo, "visitor_{$suffix}", 'visitor');

    $superLogin = login($baseUrl, $superadmin['username'], TEST_PASSWORD);
    $adminLogin = login($baseUrl, $admin['username'], TEST_PASSWORD);
    $clientLogin = login($baseUrl, $client['username'], TEST_PASSWORD);
    $visitorLogin = login($baseUrl, $visitor['username'], TEST_PASSWORD);

    $superToken = $superLogin['token'];
    $adminToken = $adminLogin['token'];
    $clientToken = $clientLogin['token'];
    $visitorToken = $visitorLogin['token'];

    expect($superLogin['user']['role'] === 'superadmin', 'Login superadmin devuelve rol correcto');
    expect($adminLogin['user']['role'] === 'admin', 'Login admin devuelve rol correcto');
    expect($clientLogin['user']['role'] === 'client', 'Login client devuelve rol correcto');
    expect($visitorLogin['user']['role'] === 'visitor', 'Login visitor devuelve rol correcto');

    $users = requestJson('GET', "{$baseUrl}/users", null, $superToken);
    expectStatus($users, 200, 'Superadmin puede listar usuarios');

    $auditSummary = requestJson('GET', "{$baseUrl}/audit/summary?hours=24", null, $superToken);
    expectStatus($auditSummary, 200, 'Superadmin puede consultar resumen de auditoria');

    $managedVisitorPayload = [
        'name' => "Visitante Gestionado {$suffix}",
        'username' => TEST_PREFIX . "managed_{$suffix}",
        'email' => TEST_PREFIX . "managed_{$suffix}@test.local",
        'phone' => '+541100000001',
        'password' => TEST_PASSWORD,
        'role' => 'visitor',
    ];
    $managedVisitor = requestJson('POST', "{$baseUrl}/users", $managedVisitorPayload, $adminToken);
    expectStatus($managedVisitor, 201, 'Admin puede crear visitantes');
    $managedVisitorId = (int)($managedVisitor['json']['data']['user_id'] ?? 0);
    expect($managedVisitorId > 0, 'Respuesta de creacion de usuario incluye user_id');

    $adminCreatesAdmin = requestJson('POST', "{$baseUrl}/users", [
        'name' => "Admin No Permitido {$suffix}",
        'username' => TEST_PREFIX . "adminfail_{$suffix}",
        'email' => TEST_PREFIX . "adminfail_{$suffix}@test.local",
        'phone' => '+541100000002',
        'password' => TEST_PASSWORD,
        'role' => 'admin',
    ], $adminToken);
    expectStatus($adminCreatesAdmin, 403, 'Admin no puede crear otro admin');

    $managedUpdate = requestJson('PUT', "{$baseUrl}/users/{$managedVisitorId}/admin", [
        'name' => "Visitante Actualizado {$suffix}",
        'phone' => '+541100000003',
        'role' => 'visitor',
    ], $adminToken);
    expectStatus($managedUpdate, 200, 'Admin puede actualizar datos no privilegiados');

    $clientUserList = requestJson('GET', "{$baseUrl}/users", null, $clientToken);
    expectStatus($clientUserList, 403, 'Client no puede listar usuarios');

    $group = requestJson('POST', "{$baseUrl}/device-groups", [
        'name' => "Grupo Flow {$suffix}",
        'description' => 'Grupo temporal generado por backend_roles_flow_test.php',
    ], $clientToken);
    expectStatus($group, 201, 'Client puede crear su grupo');
    $groupId = (int)($group['json']['data']['group_id'] ?? 0);
    expect($groupId > 0, 'Respuesta de grupo incluye group_id');

    $groupUpdate = requestJson('PUT', "{$baseUrl}/device-groups/{$groupId}", [
        'name' => "Grupo Flow Actualizado {$suffix}",
        'description' => 'Descripcion actualizada por prueba integral',
    ], $clientToken);
    expectStatus($groupUpdate, 200, 'Client puede actualizar su grupo');

    $visitorGroupCreate = requestJson('POST', "{$baseUrl}/device-groups", [
        'name' => "Grupo Visitante {$suffix}",
    ], $visitorToken);
    expectStatus($visitorGroupCreate, 403, 'Visitor no puede crear grupos');

    $deviceCode = strtoupper('FLOWT-' . $suffix);
    $device = requestJson('POST', "{$baseUrl}/devices", [
        'device_code' => $deviceCode,
        'name' => "Heladera Flow {$suffix}",
        'location' => 'Camara de prueba',
        'group_id' => $groupId,
        'min_temp' => 2,
        'max_temp' => 8,
        'firmware_version' => 'test-1.0.0',
    ], $clientToken);
    expectStatus($device, 201, 'Client puede crear heladera propia');
    $deviceId = (int)($device['json']['data']['device_id'] ?? 0);
    expect($deviceId > 0, 'Respuesta de heladera incluye device_id');

    insertTemperature($pdo, $deviceId, 4.2, '-10 minutes');
    insertTemperature($pdo, $deviceId, 5.1, '-1 minutes');

    $deviceDetail = requestJson('GET', "{$baseUrl}/devices/{$deviceId}", null, $clientToken);
    expectStatus($deviceDetail, 200, 'Client puede ver detalle de su heladera');
    $lastTemperature = (float)($deviceDetail['json']['data']['last_temperature'] ?? -999);
    expect(abs($lastTemperature - 5.1) < 0.001, 'Detalle de heladera toma la ultima temperatura registrada');

    $temperatureHistory = requestJson('GET', "{$baseUrl}/devices/{$deviceId}/temperatures?limit=10", null, $clientToken);
    expectStatus($temperatureHistory, 200, 'Client puede ver historial de temperaturas');
    $history = $temperatureHistory['json']['data'] ?? [];
    expect(count($history) >= 2, 'Historial devuelve lecturas insertadas');
    $historyLast = (float)($history[count($history) - 1]['temperature'] ?? -999);
    expect(abs($historyLast - 5.1) < 0.001, 'Historial conserva la ultima lectura al final de la serie');

    $deviceUpdate = requestJson('PUT', "{$baseUrl}/devices/{$deviceId}", [
        'name' => "Heladera Flow Actualizada {$suffix}",
        'location' => 'Camara de prueba actualizada',
        'min_temp' => 1,
        'max_temp' => 7,
        'firmware_version' => 'test-1.0.1',
    ], $clientToken);
    expectStatus($deviceUpdate, 200, 'Client puede actualizar heladera propia');

    $stock = requestJson('POST', "{$baseUrl}/devices/{$deviceId}/stock", [
        'name' => 'Leche entera',
        'quantity' => 12,
        'expiration_date' => date('Y-m-d', strtotime('+30 days')),
    ], $clientToken);
    expectStatus($stock, 201, 'Client puede crear stock');
    $stockId = (int)($stock['json']['data']['stock_id'] ?? 0);
    expect($stockId > 0, 'Respuesta de stock incluye stock_id');

    $stockUpdate = requestJson('PUT', "{$baseUrl}/stock/{$stockId}", [
        'name' => 'Leche entera',
        'quantity' => 10,
        'expiration_date' => date('Y-m-d', strtotime('+31 days')),
    ], $clientToken);
    expectStatus($stockUpdate, 200, 'Client puede actualizar stock');

    $stockList = requestJson('GET', "{$baseUrl}/devices/{$deviceId}/stock", null, $clientToken);
    expectStatus($stockList, 200, 'Client puede listar stock');
    expect(arrayContainsId($stockList['json']['data'] ?? [], $stockId), 'Listado de stock contiene el item creado');

    $grantVisitor = requestJson('POST', "{$baseUrl}/devices/{$deviceId}/grant-access", [
        'user_id' => $visitor['id'],
        'can_modify' => true,
    ], $clientToken);
    expectStatus($grantVisitor, 200, 'Client puede compartir heladera con visitor');

    $visitorDevices = requestJson('GET', "{$baseUrl}/devices", null, $visitorToken);
    expectStatus($visitorDevices, 200, 'Visitor puede listar heladeras compartidas');
    expect(arrayContainsId($visitorDevices['json']['data'] ?? [], $deviceId), 'Visitor ve la heladera compartida');

    $visitorTemps = requestJson('GET', "{$baseUrl}/devices/{$deviceId}/temperatures?limit=10", null, $visitorToken);
    expectStatus($visitorTemps, 200, 'Visitor puede ver historial de temperaturas');

    $visitorStock = requestJson('GET', "{$baseUrl}/devices/{$deviceId}/stock", null, $visitorToken);
    expectStatus($visitorStock, 200, 'Visitor puede ver stock');

    $visitorDeviceUpdate = requestJson('PUT', "{$baseUrl}/devices/{$deviceId}", [
        'name' => 'No deberia editar',
        'location' => 'Solo lectura',
        'min_temp' => 0,
        'max_temp' => 10,
    ], $visitorToken);
    expectStatus($visitorDeviceUpdate, 403, 'Visitor no puede editar heladera');

    $visitorStockCreate = requestJson('POST', "{$baseUrl}/devices/{$deviceId}/stock", [
        'name' => 'No permitido',
        'quantity' => 1,
    ], $visitorToken);
    expectStatus($visitorStockCreate, 403, 'Visitor no puede crear stock');

    $deleteStock = requestJson('DELETE', "{$baseUrl}/stock/{$stockId}", null, $clientToken);
    expectStatus($deleteStock, 200, 'Client puede borrar stock');

    $unassignGroup = requestJson('POST', "{$baseUrl}/devices/{$deviceId}/assign-group", [
        'group_id' => null,
    ], $clientToken);
    expectStatus($unassignGroup, 200, 'Client puede quitar grupo de heladera');

    $deleteGroup = requestJson('DELETE', "{$baseUrl}/device-groups/{$groupId}", null, $clientToken);
    expectStatus($deleteGroup, 200, 'Client puede borrar grupo sin heladeras asociadas');

    $auditRequests = requestJson('GET', "{$baseUrl}/audit/requests?limit=10", null, $superToken);
    expectStatus($auditRequests, 200, 'Superadmin puede consultar requests auditados');
    expect(!empty($auditRequests['json']['data']['items']), 'Auditoria de requests devuelve registros');

    $auditEvents = requestJson('GET', "{$baseUrl}/audit/events?limit=10", null, $superToken);
    expectStatus($auditEvents, 200, 'Superadmin puede consultar eventos auditados');
    expect(!empty($auditEvents['json']['data']['items']), 'Auditoria de eventos devuelve registros');

    $auditChanges = requestJson('GET', "{$baseUrl}/audit/changes?limit=20", null, $superToken);
    expectStatus($auditChanges, 200, 'Superadmin puede consultar cambios auditados');
    expect(!empty($auditChanges['json']['data']['items']), 'Auditoria de cambios devuelve registros');

    $clientAudit = requestJson('GET', "{$baseUrl}/audit/summary?hours=24", null, $clientToken);
    expectStatus($clientAudit, 403, 'Client no puede consultar auditoria administrativa');

    expectDbAtLeast($pdo, 'api_request_logs', 'path = ?', ['/api/login'], 4, 'api_request_logs registra logins');
    expectDbAtLeast($pdo, 'audit_events', 'event_type IN (?, ?, ?)', ['device_change', 'stock_item_change', 'device_group_change'], 3, 'audit_events registra cambios operativos');
}

function createVerifiedUser(PDO $pdo, string $key, string $role): array
{
    $username = TEST_PREFIX . $key;
    $email = "{$username}@test.local";
    $name = 'Usuario ' . str_replace('_', ' ', $key);

    $stmt = $pdo->prepare("
        INSERT INTO users (
            name, username, password, email, phone, role, is_email_verified, failed_login_attempts, registered_at
        ) VALUES (
            :name, :username, :password, :email, :phone, :role, 1, 0, NOW()
        )
    ");
    $stmt->execute([
        ':name' => $name,
        ':username' => $username,
        ':password' => password_hash(TEST_PASSWORD, PASSWORD_DEFAULT),
        ':email' => $email,
        ':phone' => '+541100000000',
        ':role' => $role,
    ]);

    $userId = (int)$pdo->lastInsertId();
    $verification = $pdo->prepare("
        INSERT INTO email_verifications (user_id, email, token, expires_at, verified, verified_at, ip_address)
        VALUES (:user_id, :email, :token, DATE_ADD(NOW(), INTERVAL 10 YEAR), 1, NOW(), '127.0.0.1')
    ");
    $verification->execute([
        ':user_id' => $userId,
        ':email' => $email,
        ':token' => bin2hex(random_bytes(16)),
    ]);

    return [
        'id' => $userId,
        'username' => $username,
        'email' => $email,
        'role' => $role,
    ];
}

function login(string $baseUrl, string $username, string $password): array
{
    $response = requestJson('POST', "{$baseUrl}/login", [
        'identifier' => $username,
        'password' => $password,
    ]);

    expectStatus($response, 200, "Login correcto para {$username}");
    $data = $response['json']['data'] ?? [];
    expect(!empty($data['token']), "Login devuelve token para {$username}");
    expect(!empty($data['user']['id']), "Login devuelve usuario para {$username}");

    return [
        'token' => $data['token'],
        'user' => $data['user'],
    ];
}

function requestJson(string $method, string $url, ?array $payload = null, ?string $token = null): array
{
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'User-Agent: TempSegura-BackendFlowTest/1.0',
    ];

    if ($token) {
        $headers[] = "Authorization: Bearer {$token}";
    }

    $options = [
        'http' => [
            'method' => $method,
            'ignore_errors' => true,
            'timeout' => 20,
            'header' => implode("\r\n", $headers),
        ],
    ];

    if ($payload !== null) {
        $options['http']['content'] = json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    $body = @file_get_contents($url, false, stream_context_create($options));
    if ($body === false) {
        throw new RuntimeException("No se pudo conectar con {$url}. Verifica que el backend este corriendo y BACKEND_TEST_BASE_URL sea correcto.");
    }

    $responseHeaders = $http_response_header ?? [];
    $status = 0;
    if (preg_match('#HTTP/\S+\s+(\d+)#', $responseHeaders[0] ?? '', $matches)) {
        $status = (int)$matches[1];
    }

    $json = json_decode($body, true);
    if (!is_array($json)) {
        $json = null;
    }

    return [
        'status' => $status,
        'json' => $json,
        'body' => $body,
        'headers' => $responseHeaders,
    ];
}

function insertTemperature(PDO $pdo, int $deviceId, float $temperature, string $relativeTime): void
{
    $recordedAt = date('Y-m-d H:i:s', strtotime($relativeTime));
    $stmt = $pdo->prepare("
        INSERT INTO temperatures (device_id, temperature, recorded_at)
        VALUES (:device_id, :temperature, :recorded_at)
    ");
    $stmt->execute([
        ':device_id' => $deviceId,
        ':temperature' => $temperature,
        ':recorded_at' => $recordedAt,
    ]);
}

function expectStatus(array $response, int $expectedStatus, string $label): void
{
    expect(
        $response['status'] === $expectedStatus,
        $label,
        "HTTP esperado {$expectedStatus}, recibido {$response['status']}. Body: " . trim(substr($response['body'] ?? '', 0, 700))
    );

    $json = $response['json'] ?? [];
    if (is_array($json)) {
        expect(!empty($json['request_id']), "{$label} incluye request_id");
    }
}

function expect(bool $condition, string $label, string $failureDetail = ''): void
{
    if (!$condition) {
        throw new RuntimeException($label . ($failureDetail !== '' ? "\n{$failureDetail}" : ''));
    }

    echo "[OK] {$label}\n";
}

function expectDbAtLeast(PDO $pdo, string $table, string $where, array $params, int $minimum, string $label): void
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where}");
    $stmt->execute($params);
    $count = (int)$stmt->fetchColumn();

    expect($count >= $minimum, $label, "Se esperaban al menos {$minimum} filas y se encontraron {$count}.");
}

function arrayContainsId(array $rows, int $id): bool
{
    foreach ($rows as $row) {
        if ((int)($row['id'] ?? 0) === $id) {
            return true;
        }
    }

    return false;
}

function cleanupTestData(PDO $pdo): void
{
    $pdo->exec("DELETE FROM rate_limit_events WHERE bucket LIKE 'auth-%'");

    $userIds = testUserIds($pdo);
    $deviceIds = testDeviceIds($pdo, $userIds);

    if ($deviceIds) {
        $devicePlaceholders = placeholders($deviceIds);
        $pdo->prepare("DELETE FROM stock_items WHERE device_id IN ({$devicePlaceholders})")->execute($deviceIds);
        $pdo->prepare("DELETE FROM temperatures WHERE device_id IN ({$devicePlaceholders})")->execute($deviceIds);
        $pdo->prepare("DELETE FROM device_access WHERE device_id IN ({$devicePlaceholders})")->execute($deviceIds);
        $pdo->prepare("DELETE FROM devices WHERE id IN ({$devicePlaceholders})")->execute($deviceIds);
    }

    if ($userIds) {
        $userPlaceholders = placeholders($userIds);
        $pdo->prepare("DELETE FROM device_access WHERE user_id IN ({$userPlaceholders})")->execute($userIds);
        $pdo->prepare("DELETE FROM device_groups WHERE user_id IN ({$userPlaceholders})")->execute($userIds);
        $pdo->prepare("DELETE FROM email_verifications WHERE user_id IN ({$userPlaceholders})")->execute($userIds);
        $pdo->prepare("DELETE FROM password_resets WHERE user_id IN ({$userPlaceholders})")->execute($userIds);
        $pdo->prepare("DELETE FROM users WHERE id IN ({$userPlaceholders})")->execute($userIds);
    }
}

function testUserIds(PDO $pdo): array
{
    $stmt = $pdo->prepare("
        SELECT id
        FROM users
        WHERE username LIKE :prefix OR email LIKE :email_prefix
    ");
    $stmt->execute([
        ':prefix' => TEST_PREFIX . '%',
        ':email_prefix' => TEST_PREFIX . '%@test.local',
    ]);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function testDeviceIds(PDO $pdo, array $userIds): array
{
    $conditions = ["device_code LIKE 'FLOWT-%'", "mac_address LIKE 'AA:BB:CC:44:%'"];
    $params = [];

    if ($userIds) {
        $conditions[] = 'user_id IN (' . placeholders($userIds) . ')';
        $params = array_merge($params, $userIds);
    }

    $stmt = $pdo->prepare('SELECT id FROM devices WHERE ' . implode(' OR ', $conditions));
    $stmt->execute($params);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function placeholders(array $values): string
{
    return implode(',', array_fill(0, count($values), '?'));
}

function loadEnv(string $path): array
{
    $env = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
    }

    return $env;
}

function connectDb(array $env): PDO
{
    $host = $env['DB_HOST'] ?? 'localhost';
    $name = $env['DB_NAME'] ?? '';
    $user = $env['DB_USER'] ?? '';
    $pass = $env['DB_PASS'] ?? '';

    if ($name === '') {
        throw new RuntimeException('DB_NAME no esta configurado en Backend/.env');
    }

    return new PDO(
        "mysql:host={$host};dbname={$name};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}
