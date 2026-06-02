<?php

declare(strict_types=1);

const ACL_TEST_PREFIX = 'acltest_';
const ACL_TEST_PASSWORD = 'AclTest123';
const ACL_TEST_AGENT = 'TempSegura-AccessControlSecurityTest/1.0';

$backendDir = dirname(__DIR__);
$env = loadEnv($backendDir . DIRECTORY_SEPARATOR . '.env');
$baseUrl = rtrim(getenv('BACKEND_TEST_BASE_URL') ?: 'http://127.0.0.1:8000/api', '/');
$pdo = connectDb($env);
$exitCode = 0;

try {
    cleanupAccessControlTestData($pdo);
    runAccessControlSecurityTest($pdo, $baseUrl);
    echo "\nOK seguridad ACL/IDOR por roles verificada.\n";
} catch (Throwable $exception) {
    $exitCode = 1;
    fwrite(STDERR, "\nERROR en access_control_security_test.php\n");
    fwrite(STDERR, $exception->getMessage() . "\n");
} finally {
    try {
        cleanupAccessControlTestData($pdo);
    } catch (Throwable $cleanupError) {
        $exitCode = 1;
        fwrite(STDERR, "ERROR limpiando datos de prueba ACL: " . $cleanupError->getMessage() . "\n");
    }
}

exit($exitCode);

function runAccessControlSecurityTest(PDO $pdo, string $baseUrl): void
{
    $suffix = strtolower(date('His') . bin2hex(random_bytes(2)));

    $superadmin = createVerifiedAclUser($pdo, "super_{$suffix}", 'superadmin');
    $admin = createVerifiedAclUser($pdo, "admin_{$suffix}", 'admin');
    $clientA = createVerifiedAclUser($pdo, "clienta_{$suffix}", 'client');
    $clientB = createVerifiedAclUser($pdo, "clientb_{$suffix}", 'client');
    $visitorA = createVerifiedAclUser($pdo, "visitora_{$suffix}", 'visitor');
    $visitorB = createVerifiedAclUser($pdo, "visitorb_{$suffix}", 'visitor');

    $superToken = loginAcl($baseUrl, $superadmin['username']);
    $adminToken = loginAcl($baseUrl, $admin['username']);
    $clientAToken = loginAcl($baseUrl, $clientA['username']);
    $clientBToken = loginAcl($baseUrl, $clientB['username']);
    $visitorAToken = loginAcl($baseUrl, $visitorA['username']);
    $visitorBToken = loginAcl($baseUrl, $visitorB['username']);

    $unauthenticatedDevices = requestJsonAcl('GET', "{$baseUrl}/devices");
    expectStatusAcl($unauthenticatedDevices, 401, 'Sin token no puede listar heladeras');

    $invalidTokenDevices = requestJsonAcl('GET', "{$baseUrl}/devices", null, 'token-invalido');
    expectStatusAcl($invalidTokenDevices, 401, 'Token invalido no puede listar heladeras');

    $groupA = createGroupAcl($baseUrl, $clientAToken, "Grupo ACL A {$suffix}");
    $groupB = createGroupAcl($baseUrl, $clientBToken, "Grupo ACL B {$suffix}");

    $deviceA1 = createDeviceAcl($baseUrl, $clientAToken, "ACL-A1-{$suffix}", "Heladera ACL A1 {$suffix}", $groupA['id']);
    $deviceA2 = createDeviceAcl($baseUrl, $clientAToken, "ACL-A2-{$suffix}", "Heladera ACL A2 {$suffix}", $groupA['id']);
    $deviceB1 = createDeviceAcl($baseUrl, $clientBToken, "ACL-B1-{$suffix}", "Heladera ACL B1 {$suffix}", $groupB['id']);

    insertTemperatureAcl($pdo, $deviceA1['id'], 4.1, '-20 minutes');
    insertTemperatureAcl($pdo, $deviceA1['id'], 4.7, '-2 minutes');
    insertTemperatureAcl($pdo, $deviceA2['id'], 5.2, '-3 minutes');
    insertTemperatureAcl($pdo, $deviceB1['id'], 7.4, '-1 minutes');

    $stockA1Milk = createStockAcl($baseUrl, $clientAToken, $deviceA1['id'], 'Leche ACL', 8);
    $stockA1Cheese = createStockAcl($baseUrl, $clientAToken, $deviceA1['id'], 'Queso ACL', 4);
    $stockA2Yogurt = createStockAcl($baseUrl, $clientAToken, $deviceA2['id'], 'Yogur ACL', 12);
    $stockB1Meat = createStockAcl($baseUrl, $clientBToken, $deviceB1['id'], 'Carne ACL', 3);

    $clientADevices = requestJsonAcl('GET', "{$baseUrl}/devices", null, $clientAToken);
    expectStatusAcl($clientADevices, 200, 'Client A puede listar sus heladeras');
    expectListHasIdsAcl($clientADevices['json']['data'] ?? [], [$deviceA1['id'], $deviceA2['id']], 'Client A ve sus dos heladeras');
    expectListMissingIdsAcl($clientADevices['json']['data'] ?? [], [$deviceB1['id']], 'Client A no ve heladeras de Client B en el listado');

    $clientBDevices = requestJsonAcl('GET', "{$baseUrl}/devices", null, $clientBToken);
    expectStatusAcl($clientBDevices, 200, 'Client B puede listar sus heladeras');
    expectListHasIdsAcl($clientBDevices['json']['data'] ?? [], [$deviceB1['id']], 'Client B ve su heladera');
    expectListMissingIdsAcl($clientBDevices['json']['data'] ?? [], [$deviceA1['id'], $deviceA2['id']], 'Client B no ve heladeras de Client A');

    $clientAGroups = requestJsonAcl('GET', "{$baseUrl}/device-groups", null, $clientAToken);
    expectStatusAcl($clientAGroups, 200, 'Client A puede listar sus grupos');
    expectListHasIdsAcl($clientAGroups['json']['data'] ?? [], [$groupA['id']], 'Client A ve su grupo');
    expectListMissingIdsAcl($clientAGroups['json']['data'] ?? [], [$groupB['id']], 'Client A no ve grupos de Client B');

    $adminDevices = requestJsonAcl('GET', "{$baseUrl}/devices", null, $adminToken);
    expectStatusAcl($adminDevices, 200, 'Admin puede listar heladeras de todos');
    expectListHasIdsAcl($adminDevices['json']['data'] ?? [], [$deviceA1['id'], $deviceA2['id'], $deviceB1['id']], 'Admin ve datos de ambos clientes');

    $adminDeviceUpdate = requestJsonAcl('PUT', "{$baseUrl}/devices/{$deviceB1['id']}", [
        'name' => "Heladera ACL B1 Admin {$suffix}",
        'location' => 'Sucursal auditada por admin',
        'min_temp' => 1,
        'max_temp' => 8,
        'firmware_version' => 'acl-admin-1.0',
    ], $adminToken);
    expectStatusAcl($adminDeviceUpdate, 200, 'Admin puede editar heladera de un cliente');

    $adminGroupUpdate = requestJsonAcl('PUT', "{$baseUrl}/device-groups/{$groupB['id']}", [
        'name' => "Grupo ACL B Admin {$suffix}",
        'description' => 'Grupo editado por admin en prueba ACL',
    ], $adminToken);
    expectStatusAcl($adminGroupUpdate, 200, 'Admin puede editar grupo de un cliente');

    $adminStockUpdate = requestJsonAcl('PUT', "{$baseUrl}/stock/{$stockB1Meat['id']}", [
        'name' => 'Carne ACL Admin',
        'quantity' => 2,
        'expiration_date' => date('Y-m-d', strtotime('+21 days')),
    ], $adminToken);
    expectStatusAcl($adminStockUpdate, 200, 'Admin puede editar stock de un cliente');

    $adminUserUpdate = requestJsonAcl('PUT', "{$baseUrl}/users/{$clientB['id']}/admin", [
        'name' => "Client B ACL Admin {$suffix}",
        'phone' => '+541100000123',
        'role' => 'client',
    ], $adminToken);
    expectStatusAcl($adminUserUpdate, 200, 'Admin puede editar datos de usuario cliente');

    $superAudit = requestJsonAcl('GET', "{$baseUrl}/audit/requests?limit=5", null, $superToken);
    expectStatusAcl($superAudit, 200, 'Superadmin puede leer auditoria');

    $grantVisitorA1 = requestJsonAcl('POST', "{$baseUrl}/devices/{$deviceA1['id']}/grant-access", [
        'user_id' => $visitorA['id'],
        'can_modify' => true,
    ], $clientAToken);
    expectStatusAcl($grantVisitorA1, 200, 'Client A comparte heladera A1 con Visitor A');

    $grantVisitorA2 = requestJsonAcl('POST', "{$baseUrl}/devices/{$deviceA2['id']}/grant-access", [
        'user_id' => $visitorA['id'],
        'can_modify' => true,
    ], $clientAToken);
    expectStatusAcl($grantVisitorA2, 200, 'Client A comparte heladera A2 con Visitor A');

    expectVisitorReadOnlyAcl($pdo, $visitorA['id'], [$deviceA1['id'], $deviceA2['id']]);

    $visitorADevices = requestJsonAcl('GET', "{$baseUrl}/devices", null, $visitorAToken);
    expectStatusAcl($visitorADevices, 200, 'Visitor A puede listar heladeras compartidas');
    expectListHasIdsAcl($visitorADevices['json']['data'] ?? [], [$deviceA1['id'], $deviceA2['id']], 'Visitor A ve dos heladeras compartidas');
    expectListMissingIdsAcl($visitorADevices['json']['data'] ?? [], [$deviceB1['id']], 'Visitor A no ve heladera sin acceso');

    $visitorAStockA1 = requestJsonAcl('GET', "{$baseUrl}/devices/{$deviceA1['id']}/stock", null, $visitorAToken);
    expectStatusAcl($visitorAStockA1, 200, 'Visitor A puede leer productos de A1');
    expectListHasIdsAcl($visitorAStockA1['json']['data'] ?? [], [$stockA1Milk['id'], $stockA1Cheese['id']], 'Visitor A lee varios productos si tiene acceso');

    $visitorAStockA2 = requestJsonAcl('GET', "{$baseUrl}/devices/{$deviceA2['id']}/stock", null, $visitorAToken);
    expectStatusAcl($visitorAStockA2, 200, 'Visitor A puede leer productos de A2');
    expectListHasIdsAcl($visitorAStockA2['json']['data'] ?? [], [$stockA2Yogurt['id']], 'Visitor A lee productos de la segunda heladera compartida');

    $visitorATemps = requestJsonAcl('GET', "{$baseUrl}/devices/{$deviceA1['id']}/temperatures?limit=10", null, $visitorAToken);
    expectStatusAcl($visitorATemps, 200, 'Visitor A puede leer temperaturas compartidas');

    expectForbiddenAcl('Client A no puede leer detalle de heladera B1', 'GET', "{$baseUrl}/devices/{$deviceB1['id']}", null, $clientAToken);
    expectForbiddenAcl('Client A no puede leer temperaturas de heladera B1', 'GET', "{$baseUrl}/devices/{$deviceB1['id']}/temperatures?limit=10", null, $clientAToken);
    expectForbiddenAcl('Client A no puede leer stock de heladera B1', 'GET', "{$baseUrl}/devices/{$deviceB1['id']}/stock", null, $clientAToken);
    expectForbiddenAcl('Client A no puede editar heladera B1', 'PUT', "{$baseUrl}/devices/{$deviceB1['id']}", devicePayloadAcl('Intento B1 ajeno'), $clientAToken);
    expectForbiddenAcl('Client A no puede borrar heladera B1', 'DELETE', "{$baseUrl}/devices/{$deviceB1['id']}", null, $clientAToken);
    expectForbiddenAcl('Client A no puede crear stock en heladera B1', 'POST', "{$baseUrl}/devices/{$deviceB1['id']}/stock", stockPayloadAcl('No permitido', 1), $clientAToken);
    expectForbiddenAcl('Client A no puede editar stock B1 por ID directo', 'PUT', "{$baseUrl}/stock/{$stockB1Meat['id']}", stockPayloadAcl('No permitido', 1), $clientAToken);
    expectForbiddenAcl('Client A no puede borrar stock B1 por ID directo', 'DELETE', "{$baseUrl}/stock/{$stockB1Meat['id']}", null, $clientAToken);
    expectForbiddenAcl('Client A no puede leer grupo B', 'GET', "{$baseUrl}/device-groups/{$groupB['id']}", null, $clientAToken);
    expectForbiddenAcl('Client A no puede editar grupo B', 'PUT', "{$baseUrl}/device-groups/{$groupB['id']}", groupPayloadAcl('No permitido'), $clientAToken);
    expectForbiddenAcl('Client A no puede borrar grupo B', 'DELETE', "{$baseUrl}/device-groups/{$groupB['id']}", null, $clientAToken);
    expectForbiddenAcl('Client A no puede asignar grupo propio a heladera B1', 'POST', "{$baseUrl}/devices/{$deviceB1['id']}/assign-group", ['group_id' => $groupA['id']], $clientAToken);
    expectForbiddenAcl('Client A no puede otorgar acceso sobre heladera B1', 'POST', "{$baseUrl}/devices/{$deviceB1['id']}/grant-access", ['user_id' => $visitorB['id']], $clientAToken);
    expectForbiddenAcl('Client A no puede revocar acceso sobre heladera B1', 'POST', "{$baseUrl}/devices/{$deviceB1['id']}/revoke-access", ['user_id' => $visitorB['id']], $clientAToken);
    expectForbiddenAcl('Client A no puede ver usuario Client B', 'GET', "{$baseUrl}/users/{$clientB['id']}", null, $clientAToken);
    expectForbiddenAcl('Client A no puede listar usuarios', 'GET', "{$baseUrl}/users", null, $clientAToken);

    expectForbiddenAcl('Visitor A no puede leer heladera B1 sin acceso', 'GET', "{$baseUrl}/devices/{$deviceB1['id']}", null, $visitorAToken);
    expectForbiddenAcl('Visitor A no puede leer stock B1 sin acceso', 'GET', "{$baseUrl}/devices/{$deviceB1['id']}/stock", null, $visitorAToken);
    expectForbiddenAcl('Visitor A no puede leer temperaturas B1 sin acceso', 'GET', "{$baseUrl}/devices/{$deviceB1['id']}/temperatures?limit=10", null, $visitorAToken);
    expectForbiddenAcl('Visitor A no puede editar heladera compartida aunque se haya pedido can_modify', 'PUT', "{$baseUrl}/devices/{$deviceA1['id']}", devicePayloadAcl('Visitor no edita'), $visitorAToken);
    expectForbiddenAcl('Visitor A no puede borrar heladera compartida', 'DELETE', "{$baseUrl}/devices/{$deviceA1['id']}", null, $visitorAToken);
    expectForbiddenAcl('Visitor A no puede crear stock en heladera compartida', 'POST', "{$baseUrl}/devices/{$deviceA1['id']}/stock", stockPayloadAcl('Visitor no crea', 1), $visitorAToken);
    expectForbiddenAcl('Visitor A no puede editar stock compartido por ID directo', 'PUT', "{$baseUrl}/stock/{$stockA1Milk['id']}", stockPayloadAcl('Visitor no edita', 1), $visitorAToken);
    expectForbiddenAcl('Visitor A no puede borrar stock compartido por ID directo', 'DELETE', "{$baseUrl}/stock/{$stockA1Milk['id']}", null, $visitorAToken);
    expectForbiddenAcl('Visitor A no puede crear grupos', 'POST', "{$baseUrl}/device-groups", groupPayloadAcl('Visitor no crea'), $visitorAToken);
    expectForbiddenAcl('Visitor A no puede editar grupo A', 'PUT', "{$baseUrl}/device-groups/{$groupA['id']}", groupPayloadAcl('Visitor no edita'), $visitorAToken);
    expectForbiddenAcl('Visitor A no puede borrar grupo A', 'DELETE', "{$baseUrl}/device-groups/{$groupA['id']}", null, $visitorAToken);
    expectForbiddenAcl('Visitor A no puede otorgar accesos', 'POST', "{$baseUrl}/devices/{$deviceA1['id']}/grant-access", ['user_id' => $visitorB['id']], $visitorAToken);
    expectForbiddenAcl('Visitor A no puede revocar accesos', 'POST', "{$baseUrl}/devices/{$deviceA1['id']}/revoke-access", ['user_id' => $visitorB['id']], $visitorAToken);
    expectForbiddenAcl('Visitor A no puede asignar grupos', 'POST', "{$baseUrl}/devices/{$deviceA1['id']}/assign-group", ['group_id' => null], $visitorAToken);
    expectForbiddenAcl('Visitor A no puede leer perfil de Client A', 'GET', "{$baseUrl}/users/{$clientA['id']}", null, $visitorAToken);
    expectForbiddenAcl('Visitor A no puede consultar auditoria', 'GET', "{$baseUrl}/audit/summary?hours=24", null, $visitorAToken);

    $visitorBDevices = requestJsonAcl('GET', "{$baseUrl}/devices", null, $visitorBToken);
    expectStatusAcl($visitorBDevices, 200, 'Visitor B puede listar sus accesos');
    expectListMissingIdsAcl($visitorBDevices['json']['data'] ?? [], [$deviceA1['id'], $deviceA2['id'], $deviceB1['id']], 'Visitor B no ve recursos sin acceso');

    expectForbiddenAcl('Visitor B no puede leer heladera A1 sin acceso', 'GET', "{$baseUrl}/devices/{$deviceA1['id']}", null, $visitorBToken);

    expectDbAtLeastAcl(
        $pdo,
        'api_request_logs',
        'user_agent = ? AND status_code = ?',
        [ACL_TEST_AGENT, 403],
        20,
        'Los intentos denegados quedan en api_request_logs'
    );
    expectDbAtLeastAcl(
        $pdo,
        'audit_events',
        'user_agent = ? AND event_type = ? AND entity_type = ? AND entity_id IN (?, ?)',
        [ACL_TEST_AGENT, 'device_access_change', 'device', (string)$deviceA1['id'], (string)$deviceA2['id']],
        2,
        'Los grants de acceso actuales quedan en audit_events'
    );
    expectDbAtLeastAcl(
        $pdo,
        'audit_events',
        'user_agent = ? AND event_type = ? AND entity_type = ? AND entity_id = ?',
        [ACL_TEST_AGENT, 'device_change', 'device', (string)$deviceB1['id']],
        1,
        'La edicion admin de heladera actual queda en audit_events'
    );
    expectDbAtLeastAcl(
        $pdo,
        'audit_events',
        'user_agent = ? AND event_type = ? AND entity_type = ? AND entity_id = ?',
        [ACL_TEST_AGENT, 'stock_item_change', 'stock_item', (string)$stockB1Meat['id']],
        1,
        'La edicion admin de stock actual queda en audit_events'
    );
}

function createVerifiedAclUser(PDO $pdo, string $key, string $role): array
{
    $username = ACL_TEST_PREFIX . $key;
    $email = "{$username}@test.local";
    $name = 'ACL Usuario ' . str_replace('_', ' ', $key);

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
        ':password' => password_hash(ACL_TEST_PASSWORD, PASSWORD_DEFAULT),
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

function loginAcl(string $baseUrl, string $username): string
{
    $response = requestJsonAcl('POST', "{$baseUrl}/login", [
        'identifier' => $username,
        'password' => ACL_TEST_PASSWORD,
    ]);

    expectStatusAcl($response, 200, "Login correcto para {$username}");
    $token = $response['json']['data']['token'] ?? '';
    expectAcl(is_string($token) && $token !== '', "Login devuelve token para {$username}");
    return $token;
}

function createGroupAcl(string $baseUrl, string $token, string $name): array
{
    $response = requestJsonAcl('POST', "{$baseUrl}/device-groups", groupPayloadAcl($name), $token);
    expectStatusAcl($response, 201, "Grupo creado: {$name}");
    $id = (int)($response['json']['data']['group_id'] ?? 0);
    expectAcl($id > 0, "Respuesta de grupo {$name} incluye group_id");

    return ['id' => $id, 'name' => $name];
}

function createDeviceAcl(string $baseUrl, string $token, string $code, string $name, int $groupId): array
{
    $response = requestJsonAcl('POST', "{$baseUrl}/devices", [
        'device_code' => $code,
        'name' => $name,
        'location' => 'Sucursal ACL',
        'group_id' => $groupId,
        'min_temp' => 2,
        'max_temp' => 8,
        'firmware_version' => 'acl-test-1.0',
    ], $token);
    expectStatusAcl($response, 201, "Heladera creada: {$code}");
    $id = (int)($response['json']['data']['device_id'] ?? 0);
    expectAcl($id > 0, "Respuesta de heladera {$code} incluye device_id");

    return ['id' => $id, 'code' => $code, 'name' => $name];
}

function createStockAcl(string $baseUrl, string $token, int $deviceId, string $name, int $quantity): array
{
    $response = requestJsonAcl('POST', "{$baseUrl}/devices/{$deviceId}/stock", stockPayloadAcl($name, $quantity), $token);
    expectStatusAcl($response, 201, "Stock creado: {$name}");
    $id = (int)($response['json']['data']['stock_id'] ?? 0);
    expectAcl($id > 0, "Respuesta de stock {$name} incluye stock_id");

    return ['id' => $id, 'name' => $name];
}

function groupPayloadAcl(string $name): array
{
    return [
        'name' => $name,
        'description' => 'Dato temporal generado por access_control_security_test.php',
    ];
}

function devicePayloadAcl(string $name): array
{
    return [
        'name' => $name,
        'location' => 'Intento ACL',
        'min_temp' => 2,
        'max_temp' => 8,
        'firmware_version' => 'acl-test-denied',
    ];
}

function stockPayloadAcl(string $name, int $quantity): array
{
    return [
        'name' => $name,
        'quantity' => $quantity,
        'expiration_date' => date('Y-m-d', strtotime('+30 days')),
    ];
}

function requestJsonAcl(string $method, string $url, ?array $payload = null, ?string $token = null): array
{
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'User-Agent: ' . ACL_TEST_AGENT,
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

function insertTemperatureAcl(PDO $pdo, int $deviceId, float $temperature, string $relativeTime): void
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

function expectForbiddenAcl(string $label, string $method, string $url, ?array $payload, string $token): void
{
    $response = requestJsonAcl($method, $url, $payload, $token);
    expectStatusAcl($response, 403, $label);
}

function expectStatusAcl(array $response, int $expectedStatus, string $label): void
{
    expectAcl(
        $response['status'] === $expectedStatus,
        $label,
        "HTTP esperado {$expectedStatus}, recibido {$response['status']}. Body: " . trim(substr($response['body'] ?? '', 0, 700))
    );

    $json = $response['json'] ?? [];
    if (is_array($json)) {
        expectAcl(!empty($json['request_id']), "{$label} incluye request_id");
    }
}

function expectAcl(bool $condition, string $label, string $failureDetail = ''): void
{
    if (!$condition) {
        throw new RuntimeException($label . ($failureDetail !== '' ? "\n{$failureDetail}" : ''));
    }

    echo "[OK] {$label}\n";
}

function expectListHasIdsAcl(array $rows, array $ids, string $label): void
{
    foreach ($ids as $id) {
        if (!arrayContainsIdAcl($rows, (int)$id)) {
            throw new RuntimeException("{$label}\nFalta el id {$id} en la respuesta.");
        }
    }

    echo "[OK] {$label}\n";
}

function expectListMissingIdsAcl(array $rows, array $ids, string $label): void
{
    foreach ($ids as $id) {
        if (arrayContainsIdAcl($rows, (int)$id)) {
            throw new RuntimeException("{$label}\nEl id {$id} aparecio indebidamente en la respuesta.");
        }
    }

    echo "[OK] {$label}\n";
}

function arrayContainsIdAcl(array $rows, int $id): bool
{
    foreach ($rows as $row) {
        if ((int)($row['id'] ?? 0) === $id) {
            return true;
        }
    }

    return false;
}

function expectVisitorReadOnlyAcl(PDO $pdo, int $visitorId, array $deviceIds): void
{
    $stmt = $pdo->prepare('SELECT can_modify FROM device_access WHERE user_id = ? AND device_id = ?');
    foreach ($deviceIds as $deviceId) {
        $stmt->execute([$visitorId, $deviceId]);
        $value = $stmt->fetchColumn();
        expectAcl($value !== false, "Visitor tiene acceso registrado a heladera {$deviceId}");
        expectAcl((int)$value === 0, "Visitor queda siempre solo lectura en heladera {$deviceId}");
    }
}

function expectDbAtLeastAcl(PDO $pdo, string $table, string $where, array $params, int $minimum, string $label): void
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where}");
    $stmt->execute($params);
    $count = (int)$stmt->fetchColumn();

    expectAcl($count >= $minimum, $label, "Se esperaban al menos {$minimum} filas y se encontraron {$count}.");
}

function cleanupAccessControlTestData(PDO $pdo): void
{
    $pdo->exec("DELETE FROM rate_limit_events WHERE bucket LIKE 'auth-%'");

    $userIds = aclTestUserIds($pdo);
    $deviceIds = aclTestDeviceIds($pdo, $userIds);

    if ($deviceIds) {
        $devicePlaceholders = placeholdersAcl($deviceIds);
        $pdo->prepare("DELETE FROM stock_items WHERE device_id IN ({$devicePlaceholders})")->execute($deviceIds);
        $pdo->prepare("DELETE FROM temperatures WHERE device_id IN ({$devicePlaceholders})")->execute($deviceIds);
        $pdo->prepare("DELETE FROM device_access WHERE device_id IN ({$devicePlaceholders})")->execute($deviceIds);
        $pdo->prepare("DELETE FROM devices WHERE id IN ({$devicePlaceholders})")->execute($deviceIds);
    }

    if ($userIds) {
        $userPlaceholders = placeholdersAcl($userIds);
        $pdo->prepare("DELETE FROM device_access WHERE user_id IN ({$userPlaceholders})")->execute($userIds);
        $pdo->prepare("DELETE FROM device_groups WHERE user_id IN ({$userPlaceholders})")->execute($userIds);
        $pdo->prepare("DELETE FROM email_verifications WHERE user_id IN ({$userPlaceholders})")->execute($userIds);
        $pdo->prepare("DELETE FROM password_resets WHERE user_id IN ({$userPlaceholders})")->execute($userIds);
        $pdo->prepare("DELETE FROM users WHERE id IN ({$userPlaceholders})")->execute($userIds);
    }
}

function aclTestUserIds(PDO $pdo): array
{
    $stmt = $pdo->prepare("
        SELECT id
        FROM users
        WHERE username LIKE :prefix OR email LIKE :email_prefix
    ");
    $stmt->execute([
        ':prefix' => ACL_TEST_PREFIX . '%',
        ':email_prefix' => ACL_TEST_PREFIX . '%@test.local',
    ]);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function aclTestDeviceIds(PDO $pdo, array $userIds): array
{
    $conditions = ["device_code LIKE 'ACL-%'"];
    $params = [];

    if ($userIds) {
        $conditions[] = 'user_id IN (' . placeholdersAcl($userIds) . ')';
        $params = array_merge($params, $userIds);
    }

    $stmt = $pdo->prepare('SELECT id FROM devices WHERE ' . implode(' OR ', $conditions));
    $stmt->execute($params);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function placeholdersAcl(array $values): string
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
