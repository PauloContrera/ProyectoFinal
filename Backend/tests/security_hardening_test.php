<?php

/**
 * Cobertura de los endurecimientos de seguridad:
 *
 *  1. Bloqueo de cuenta con ventana temporal (users.locked_until) y desbloqueo
 *     automatico al vencer, en lugar del bloqueo permanente anterior.
 *  2. Login sin fuga de existencia de cuenta: usuario inexistente, contraseña
 *     incorrecta y cuenta bloqueada con clave incorrecta responden todos igual.
 *  3. ESP sin shared_secret propio: se rechaza en vez de caer a un secreto por
 *     defecto conocido.
 *  4. Clave de activacion por dispositivo: una heladera preprovisionada no acepta
 *     la palabra_clave global.
 *
 * Requiere el backend en vivo (php -S 127.0.0.1:8000 -t Backend/public) y MySQL.
 */

declare(strict_types=1);

const LOCK_USER = 'sechardening';
const LOCK_EMAIL = 'sechardening@example.test';
const LOCK_PASSWORD = 'Test1234';
const NO_SECRET_MAC = 'AA:BB:CC:DD:EE:71';
const OWN_KEYWORD_MAC = 'AA:BB:CC:DD:EE:72';

$backendDir = dirname(__DIR__);
$env = loadEnv($backendDir . DIRECTORY_SEPARATOR . '.env');
$base = rtrim(getenv('BACKEND_TEST_BASE_URL') ?: 'http://127.0.0.1:8000/api', '/');
$pdo = connectDb($env);

$pass = 0;
$fail = 0;

cleanup($pdo);

try {
    seedUser($pdo);

    // ---------------------------------------------------------------- 1 + 2
    // Referencia: usuario que no existe.
    $inexistente = req('POST', $base . '/login', [
        'identifier' => 'no-existe-jamas-' . bin2hex(random_bytes(4)),
        'password' => 'Loquesea1',
    ]);
    check('Usuario inexistente responde 401', $inexistente['http'] === 401, (string)$inexistente['http']);
    $mensajeGenerico = $inexistente['json']['message'] ?? '';

    // Contraseña incorrecta sobre un usuario que SI existe: misma respuesta.
    $claveMala = req('POST', $base . '/login', [
        'identifier' => LOCK_USER,
        'password' => 'ClaveIncorrecta9',
    ]);
    check('Contraseña incorrecta responde 401', $claveMala['http'] === 401, (string)$claveMala['http']);
    check(
        'Usuario inexistente y clave incorrecta dan el mismo mensaje',
        ($claveMala['json']['message'] ?? '') === $mensajeGenerico,
        json_encode([$mensajeGenerico, $claveMala['json']['message'] ?? null])
    );

    // Agotar los intentos hasta bloquear la cuenta.
    $maxIntentos = (int)($env['ACCOUNT_LOCK_ATTEMPTS'] ?? 5);
    for ($i = 1; $i < $maxIntentos; $i++) {
        req('POST', $base . '/login', ['identifier' => LOCK_USER, 'password' => 'ClaveIncorrecta9']);
    }

    $lockedUntil = fetchLockedUntil($pdo);
    check('Tras agotar intentos, locked_until queda seteado', $lockedUntil !== null, var_export($lockedUntil, true));
    // La comparacion la hace MySQL: el servidor de BD y el de PHP pueden estar en
    // husos distintos, asi que strtotime() aca daria un falso negativo.
    check(
        'locked_until apunta al futuro',
        lockIsInTheFuture($pdo),
        (string)$lockedUntil
    );

    // Bloqueada + clave incorrecta: indistinguible de un usuario inexistente.
    $bloqueadaClaveMala = req('POST', $base . '/login', [
        'identifier' => LOCK_USER,
        'password' => 'ClaveIncorrecta9',
    ]);
    check('Cuenta bloqueada con clave incorrecta responde 401', $bloqueadaClaveMala['http'] === 401, (string)$bloqueadaClaveMala['http']);
    check(
        'Cuenta bloqueada con clave incorrecta no revela el bloqueo',
        ($bloqueadaClaveMala['json']['message'] ?? '') === $mensajeGenerico,
        json_encode($bloqueadaClaveMala['json']['message'] ?? null)
    );

    // Bloqueada + clave correcta: al dueño si se le informa el bloqueo.
    $bloqueadaClaveOk = req('POST', $base . '/login', [
        'identifier' => LOCK_USER,
        'password' => LOCK_PASSWORD,
    ]);
    check('Cuenta bloqueada con clave correcta responde 403', $bloqueadaClaveOk['http'] === 403, (string)$bloqueadaClaveOk['http']);
    check(
        'Cuenta bloqueada con clave correcta no entrega token',
        empty($bloqueadaClaveOk['json']['data']['token']),
        json_encode($bloqueadaClaveOk['json']['data'] ?? null)
    );

    // Desbloqueo automatico: se simula una ventana ya vencida.
    $pdo->prepare('UPDATE users SET locked_until = DATE_SUB(NOW(), INTERVAL 1 MINUTE) WHERE username = ?')
        ->execute([LOCK_USER]);

    $trasVencimiento = req('POST', $base . '/login', [
        'identifier' => LOCK_USER,
        'password' => LOCK_PASSWORD,
    ]);
    check('Vencida la ventana, el login vuelve a funcionar', ($trasVencimiento['json']['success'] ?? false) === true, json_encode($trasVencimiento['json']['message'] ?? null));
    check('Tras desbloquear, locked_until vuelve a NULL', fetchLockedUntil($pdo) === null);

    // ---------------------------------------------------------------------- 3
    // Heladera con MAC pero sin shared_secret: el sync debe rechazarse.
    seedDevice($pdo, NO_SECRET_MAC, 'ESP-SEC-NOSECRET', null, 'clave-propia-71');
    $syncSinSecreto = signedSync($base, NO_SECRET_MAC, 'local-dev-esp-secret');
    check('Dispositivo sin shared_secret rechaza el sync (401)', $syncSinSecreto['http'] === 401, (string)$syncSinSecreto['http']);
    check(
        'Dispositivo sin shared_secret devuelve ERR_FIRMA',
        ($syncSinSecreto['json']['error']['code'] ?? '') === 'ERR_FIRMA',
        json_encode($syncSinSecreto['json']['error'] ?? null)
    );

    // El registro reprovisiona el secreto faltante y deja el sync operativo.
    $reprovision = req('POST', $base . '/esp/register', [
        'accion' => 'registro',
        'mac' => NO_SECRET_MAC,
        'modelo' => 'ESP32-TEST',
        'timestamp' => time(),
        'palabra_clave' => 'clave-propia-71',
    ]);
    $nuevoSecreto = $reprovision['json']['provisioning']['shared_secret'] ?? null;
    check('Registro reprovisiona el shared_secret faltante', !empty($nuevoSecreto));
    check(
        'Con el secreto nuevo el sync es aceptado',
        !empty($nuevoSecreto) && signedSync($base, NO_SECRET_MAC, (string)$nuevoSecreto)['http'] === 200
    );

    // ---------------------------------------------------------------------- 4
    // Heladera preprovisionada con clave propia: la global no sirve.
    $claveGlobal = $env['ESP_ACTIVATION_KEYWORD'] ?? 'clavesecreta4321';
    seedDevice($pdo, OWN_KEYWORD_MAC, 'ESP-SEC-OWNKEY', bin2hex(random_bytes(32)), 'clave-propia-72');

    $conClaveGlobal = req('POST', $base . '/esp/register', [
        'accion' => 'registro',
        'mac' => OWN_KEYWORD_MAC,
        'modelo' => 'ESP32-TEST',
        'timestamp' => time(),
        'palabra_clave' => $claveGlobal,
    ]);
    check('Clave global rechazada en heladera con clave propia (401)', $conClaveGlobal['http'] === 401, (string)$conClaveGlobal['http']);
    check(
        'Clave global rechazada devuelve ERR_ACTIVACION',
        ($conClaveGlobal['json']['error']['code'] ?? '') === 'ERR_ACTIVACION',
        json_encode($conClaveGlobal['json']['error'] ?? null)
    );

    $conClavePropia = req('POST', $base . '/esp/register', [
        'accion' => 'registro',
        'mac' => OWN_KEYWORD_MAC,
        'modelo' => 'ESP32-TEST',
        'timestamp' => time(),
        'palabra_clave' => 'clave-propia-72',
    ]);
    check('Clave propia aceptada (200)', $conClavePropia['http'] === 200, (string)$conClavePropia['http']);
    check(
        'Registro no filtra el shared_secret de una heladera ya provisionada',
        !isset($conClavePropia['json']['provisioning']),
        json_encode(array_keys($conClavePropia['json'] ?? []))
    );
} finally {
    cleanup($pdo);
}

echo "\nRESULTADO: {$pass} OK / {$fail} FAIL\n";
exit($fail === 0 ? 0 : 1);

// ---------------------------------------------------------------------------

function check(string $label, bool $condition, string $extra = ''): void
{
    global $pass, $fail;
    if ($condition) {
        $pass++;
        echo "[OK] {$label}\n";
    } else {
        $fail++;
        echo "[FAIL] {$label}  {$extra}\n";
    }
}

function seedUser(PDO $pdo): void
{
    $stmt = $pdo->prepare('
        INSERT INTO users (name, username, password, email, role, is_email_verified, failed_login_attempts)
        VALUES (:name, :username, :password, :email, "client", 1, 0)
    ');
    $stmt->execute([
        ':name' => 'Security Hardening',
        ':username' => LOCK_USER,
        ':password' => password_hash(LOCK_PASSWORD, PASSWORD_DEFAULT),
        ':email' => LOCK_EMAIL,
    ]);

    // El login exige un email_verifications con verified = 1.
    $userId = (int)$pdo->lastInsertId();
    $pdo->prepare('
        INSERT INTO email_verifications (user_id, email, token, ip_address, expires_at, verified, verified_at)
        VALUES (?, ?, ?, "127.0.0.1", DATE_ADD(NOW(), INTERVAL 1 DAY), 1, NOW())
    ')->execute([$userId, LOCK_EMAIL, bin2hex(random_bytes(16))]);
}

function seedDevice(PDO $pdo, string $mac, string $code, ?string $secret, string $keyword): void
{
    $pdo->prepare('
        INSERT INTO devices (device_code, mac_address, shared_secret, activation_keyword,
                             name, location, min_temp, max_temp, account_enabled, send_interval_seconds, config_version)
        VALUES (?, ?, ?, ?, ?, "Test", 2, 8, 1, 900, 1)
    ')->execute([$code, $mac, $secret, $keyword, 'Heladera ' . $code]);
}

function lockIsInTheFuture(PDO $pdo): bool
{
    $stmt = $pdo->prepare('SELECT locked_until > NOW() FROM users WHERE username = ?');
    $stmt->execute([LOCK_USER]);

    return (bool)$stmt->fetchColumn();
}

function fetchLockedUntil(PDO $pdo): ?string
{
    $stmt = $pdo->prepare('SELECT locked_until FROM users WHERE username = ?');
    $stmt->execute([LOCK_USER]);
    $value = $stmt->fetchColumn();

    return $value === false || $value === null ? null : (string)$value;
}

function signedSync(string $base, string $mac, string $secret): array
{
    $now = time();
    $payload = [
        'mac' => $mac,
        'timestamp' => $now,
        'packet_id' => 'sec-' . $now . '-' . bin2hex(random_bytes(4)),
        'seq' => 1,
        'data' => [['temp' => 4.5, 'time' => $now - 30]],
        'local_alerts' => [],
    ];

    $forSignature = $payload;
    unset($forSignature['mac'], $forSignature['timestamp'], $forSignature['signature']);
    $json = json_encode(canonical($forSignature), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $payload['signature'] = hash_hmac('sha256', $mac . $now . $json, $secret);

    return req('POST', $base . '/esp/sync', $payload);
}

function canonical($value)
{
    if (!is_array($value)) return $value;
    if (array_keys($value) === range(0, count($value) - 1)) {
        return array_map('canonical', $value);
    }
    ksort($value);
    foreach ($value as $key => $item) {
        $value[$key] = canonical($item);
    }
    return $value;
}

function req(string $method, string $url, ?array $body = null, ?string $token = null): array
{
    $ch = curl_init($url);
    $headers = ['Content-Type: application/json'];
    if ($token) $headers[] = 'Authorization: Bearer ' . $token;

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['http' => $code, 'json' => json_decode((string)$raw, true)];
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

function cleanup(PDO $pdo): void
{
    // Los buckets de rate limit se limpian para que los reintentos de login del
    // test no choquen con el limite por IP/identificador.
    $pdo->exec("DELETE FROM rate_limit_events WHERE bucket LIKE 'auth-%' OR bucket LIKE 'esp-%'");
    $pdo->exec("DELETE FROM blocked_ips WHERE ip_address IN ('127.0.0.1', '::1')");

    // Tambien los login_failed recientes de localhost: checkAndBlockIp() los cuenta
    // sobre una ventana de 30 minutos, asi que sin esto la segunda corrida del test
    // arranca con la IP ya bloqueada por los fallos que genero la primera.
    $pdo->exec("
        DELETE FROM event_logs
        WHERE event_type IN ('login_failed', 'ip_blocked')
          AND ip_address IN ('127.0.0.1', '::1')
          AND created_at > (NOW() - INTERVAL 2 HOUR)
    ");

    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $stmt->execute([LOCK_USER]);
    $userId = $stmt->fetchColumn();

    if ($userId !== false) {
        $pdo->prepare('DELETE FROM email_verifications WHERE user_id = ?')->execute([$userId]);
        $pdo->prepare('DELETE FROM event_logs WHERE user_id = ?')->execute([$userId]);
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
    }

    $pdo->prepare('DELETE FROM devices WHERE mac_address IN (?, ?)')
        ->execute([NO_SECRET_MAC, OWN_KEYWORD_MAC]);
}
