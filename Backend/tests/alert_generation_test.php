<?php
// Test de generacion de alertas de temperatura server-side.
// Usa el device seed DEV-G1-H1 (MAC A1:B2:C3:D4:E5:01, secret local-dev-esp-secret,
// rango configurado min 2.2 / max 8.4). Requiere el server en vivo y el usuario
// devtest con password Test1234 (dueno de la heladera).
declare(strict_types=1);

$base = getenv('BACKEND_TEST_BASE_URL') ?: 'http://127.0.0.1:8000/api';
$mac = 'A1:B2:C3:D4:E5:01';
$secret = 'local-dev-esp-secret';
$pass = 0; $fail = 0;

function loadEnvFile(string $path): array {
    $env = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim($v);
    }
    return $env;
}
function connectDb(array $env): PDO {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $env['DB_HOST'] ?? 'localhost', $env['DB_NAME'] ?? 'temp_segura');
    return new PDO($dsn, $env['DB_USER'] ?? 'root', $env['DB_PASS'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}
/**
 * Borra lecturas y alertas recientes de la heladera de prueba.
 *
 * Hace falta porque el test fija los recorded_at como time()-50, -40, -30...: si
 * una corrida anterior dejo una lectura en ese mismo segundo, el sync la descarta
 * por duplicada, nunca se evalua la alerta y el test falla sin que haya un bug.
 */
function resetDeviceState(PDO $pdo, string $deviceCode): void {
    $stmt = $pdo->prepare('SELECT id FROM devices WHERE device_code = ?');
    $stmt->execute([$deviceCode]);
    $deviceId = $stmt->fetchColumn();
    if ($deviceId === false) return;

    $pdo->prepare('DELETE FROM alerts WHERE device_id = ?')->execute([$deviceId]);
    $pdo->prepare('DELETE FROM temperatures WHERE device_id = ? AND recorded_at > DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY)')->execute([$deviceId]);
    $pdo->prepare('DELETE FROM esp_sync_batches WHERE device_id = ?')->execute([$deviceId]);
    $pdo->exec("DELETE FROM rate_limit_events WHERE bucket LIKE 'esp-%'");
}

resetDeviceState(connectDb(loadEnvFile(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env')), 'DEV-G1-H1');
function check(string $label, bool $cond, $extra = '') {
    global $pass, $fail;
    if ($cond) { $pass++; echo "[OK] $label\n"; }
    else { $fail++; echo "[FAIL] $label  $extra\n"; }
}
function canon($v) {
    if (!is_array($v)) return $v;
    $isList = array_keys($v) === range(0, count($v) - 1);
    if ($isList) return array_map('canon', $v);
    ksort($v);
    foreach ($v as $k => $x) $v[$k] = canon($x);
    return $v;
}
function req(string $method, string $url, ?array $body = null, ?string $token = null): array {
    $ch = curl_init($url);
    $headers = ['Content-Type: application/json'];
    if ($token) $headers[] = 'Authorization: Bearer ' . $token;
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['http' => $code, 'json' => json_decode($raw, true)];
}
function signedSync(string $base, string $mac, string $secret, float $temp, int $time, int $seq): array {
    $p = [
        'mac' => $mac, 'timestamp' => time(),
        'packet_id' => "alert-test-{$time}-{$seq}", 'seq' => $seq,
        'data' => [['temp' => $temp, 'time' => $time]],
        'local_alerts' => [],
    ];
    $forSig = $p; unset($forSig['mac'], $forSig['timestamp'], $forSig['signature']);
    $json = json_encode(canon($forSig), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $p['signature'] = hash_hmac('sha256', $mac . $p['timestamp'] . $json, $secret);
    return req('POST', $base . '/esp/sync', $p);
}

// Login devtest (dueno de DEV-G1-H1)
$login = req('POST', $base . '/login', ['identifier' => 'devtest', 'password' => 'Test1234']);
check('Login devtest OK', ($login['json']['success'] ?? false) === true, json_encode($login['json']['message'] ?? null));
$token = $login['json']['data']['token'] ?? null;
if (!$token) { echo "\nNo hay token, abortando.\n"; exit(1); }

// Encontrar device DEV-G1-H1
$devices = req('GET', $base . '/devices', null, $token);
$deviceId = null;
foreach (($devices['json']['data'] ?? []) as $d) {
    if (($d['device_code'] ?? '') === 'DEV-G1-H1') { $deviceId = (int)$d['id']; break; }
}
check('Device DEV-G1-H1 encontrado', $deviceId !== null, 'no se encontro');
if (!$deviceId) { exit(1); }

$alertsUrl = $base . '/devices/' . $deviceId . '/alerts';
$countAlerts = function () use ($alertsUrl, $token): array {
    $r = req('GET', $alertsUrl, null, $token);
    return $r['json']['data'] ?? [];
};

$now = time();

// 1) Lectura muy por encima del max (8.4) -> debe crear alerta TEMP_HIGH
signedSync($base, $mac, $secret, 15.0, $now - 50, 1);
$a1 = $countAlerts();
$unresolvedHigh = array_values(array_filter($a1, fn($x) => $x['type'] === 'TEMP_HIGH' && (int)$x['resolved'] === 0));
check('Lectura > max genera alerta TEMP_HIGH', count($unresolvedHigh) >= 1, 'alertas=' . count($a1));
$baseCount = count($a1);

// 2) Otra lectura alta dentro del cooldown -> NO debe crear otra (dedup)
signedSync($base, $mac, $secret, 16.0, $now - 40, 2);
$a2 = $countAlerts();
check('Segunda lectura alta no duplica alerta (cooldown)', count($a2) === $baseCount, 'antes=' . $baseCount . ' despues=' . count($a2));

// 3) Lectura en rango -> NO genera alerta
signedSync($base, $mac, $secret, 5.0, $now - 30, 3);
$a3 = $countAlerts();
check('Lectura en rango no genera alerta', count($a3) === $baseCount, 'antes=' . $baseCount . ' despues=' . count($a3));

// 4) Lectura por debajo del min (2.2) -> nueva alerta TEMP_LOW (otro tipo)
signedSync($base, $mac, $secret, -3.0, $now - 20, 4);
$a4 = $countAlerts();
$hasLow = count(array_filter($a4, fn($x) => $x['type'] === 'TEMP_LOW' && (int)$x['resolved'] === 0)) >= 1;
check('Lectura < min genera alerta TEMP_LOW', $hasLow && count($a4) === $baseCount + 1, 'total=' . count($a4));

// 5) Endpoint lista alertas con estructura esperada
$sample = $a4[0] ?? [];
check('Endpoint de alertas devuelve campos esperados', isset($sample['type'], $sample['temperature'], $sample['resolved']));

// 6) Resolver una alerta TEMP_HIGH y re-armar
$highToResolve = array_values(array_filter($a4, fn($x) => $x['type'] === 'TEMP_HIGH' && (int)$x['resolved'] === 0))[0] ?? null;
check('Hay alerta TEMP_HIGH para resolver', $highToResolve !== null);
if ($highToResolve) {
    $res = req('PUT', $base . '/alerts/' . (int)$highToResolve['id'] . '/resolve', null, $token);
    check('Resolver alerta HTTP 200', $res['http'] === 200, 'http=' . $res['http']);
    $a5 = $countAlerts();
    $resolvedNow = count(array_filter($a5, fn($x) => (int)$x['id'] === (int)$highToResolve['id'] && (int)$x['resolved'] === 1)) === 1;
    check('La alerta queda marcada como resuelta', $resolvedNow);

    // Tras resolver, una nueva lectura alta debe re-armar la alerta
    signedSync($base, $mac, $secret, 17.0, $now - 10, 5);
    $a6 = $countAlerts();
    $newHighUnresolved = count(array_filter($a6, fn($x) => $x['type'] === 'TEMP_HIGH' && (int)$x['resolved'] === 0)) >= 1;
    check('Tras resolver, nueva lectura alta re-arma alerta', $newHighUnresolved && count($a6) === count($a5) + 1, 'total=' . count($a6));
}

// 7) Visitante/otro no puede resolver alertas que no le corresponden (ACL): sin token -> 401
$noAuth = req('GET', $alertsUrl, null, null);
check('Sin token no puede listar alertas (401)', $noAuth['http'] === 401, 'http=' . $noAuth['http']);

// 8) Alarmero global: GET /api/alerts incluye device_name
$alarmero = req('GET', $base . '/alerts?status=open', null, $token);
check('Alarmero GET /api/alerts HTTP 200', $alarmero['http'] === 200, 'http=' . $alarmero['http']);
$alarmeroData = $alarmero['json']['data'] ?? [];
$tieneDeviceName = count($alarmeroData) > 0 && isset($alarmeroData[0]['device_name']);
check('Alarmero incluye device_name', $tieneDeviceName);

// 9) Acknowledge: reconocer una alarma activa la mueve de active -> acknowledged
$activeList = req('GET', $base . '/alerts?status=active', null, $token);
$activa = $activeList['json']['data'][0] ?? null;
check('Hay alarma activa para reconocer', $activa !== null);
if ($activa) {
    $ack = req('PUT', $base . '/alerts/' . (int)$activa['id'] . '/acknowledge', null, $token);
    check('Acknowledge HTTP 200', $ack['http'] === 200, 'http=' . $ack['http']);

    $activeAfter = req('GET', $base . '/alerts?status=active', null, $token);
    $stillActive = false;
    foreach (($activeAfter['json']['data'] ?? []) as $a) {
        if ((int)$a['id'] === (int)$activa['id']) { $stillActive = true; break; }
    }
    check('Tras reconocer, ya no esta en status=active', !$stillActive);

    $ackList = req('GET', $base . '/alerts?status=acknowledged', null, $token);
    $inAck = false;
    foreach (($ackList['json']['data'] ?? []) as $a) {
        if ((int)$a['id'] === (int)$activa['id']) { $inAck = true; break; }
    }
    check('Tras reconocer, aparece en status=acknowledged', $inAck);
}

// 10) Email de alerta capturado en dev (logs/mail_dev.log)
$logFile = __DIR__ . '/../logs/mail_dev.log';
$logContent = is_file($logFile) ? (string)file_get_contents($logFile) : '';
check('Email de alerta capturado en mail_dev.log', strpos($logContent, '[Temp Segura]') !== false && strpos($logContent, 'detectada') !== false);

echo "\nRESULTADO: $pass OK / $fail FAIL\n";
exit($fail === 0 ? 0 : 1);
