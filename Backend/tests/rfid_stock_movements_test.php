<?php

/**
 * Movimientos de stock disparados por RFID.
 *
 * Simula el ESP: registra una heladera, carga items con tarjeta y manda lecturas
 * firmadas dentro de /api/esp/sync verificando que la cantidad suba y baje.
 *
 * Requiere el backend en vivo y MySQL con la migracion 007 aplicada.
 */

declare(strict_types=1);

const RFID_MAC = 'AA:BB:CC:DD:EE:60';
const UID_A = '3A:5C:33:02';   // vacuna1 del firmware
const UID_B = '33:94:BC:D9';   // vacuna2 del firmware
const UID_DESCONOCIDO = 'FF:FF:FF:FF';

$backendDir = dirname(__DIR__);
$env = loadEnv($backendDir . DIRECTORY_SEPARATOR . '.env');
$base = rtrim(getenv('BACKEND_TEST_BASE_URL') ?: 'http://127.0.0.1:8000/api', '/');
$keyword = $env['ESP_ACTIVATION_KEYWORD'] ?? 'clavesecreta4321';
$pdo = connectDb($env);

$pass = 0;
$fail = 0;
$seq = 0;

cleanup($pdo);

try {
    // --- Alta del lector -----------------------------------------------------
    $register = post($base . '/esp/register', [
        'accion' => 'registro',
        'mac' => RFID_MAC,
        'modelo' => 'ESP32 + MFRC522',
        'timestamp' => time(),
        'palabra_clave' => $keyword,
    ]);
    check('Registro del lector HTTP 200', $register['status'] === 200, (string)$register['status']);
    $secret = $register['json']['provisioning']['shared_secret'] ?? null;
    check('Entrega shared_secret', !empty($secret));
    if (!$secret) throw new RuntimeException('sin secreto');

    $deviceId = (int)$pdo->query('SELECT id FROM devices WHERE mac_address = ' . $pdo->quote(RFID_MAC))->fetchColumn();
    check('Heladera creada', $deviceId > 0);

    // El alta por /esp/register deja la heladera sin dueño. Se le asigna devtest
    // para poder probar los endpoints de consulta con un usuario client real.
    $ownerId = (int)$pdo->query("SELECT id FROM users WHERE username = 'devtest'")->fetchColumn();
    check('Usuario devtest existe', $ownerId > 0);
    $pdo->prepare('UPDATE devices SET user_id = ? WHERE id = ?')->execute([$ownerId, $deviceId]);

    // Dos items con tarjeta. El B se carga por duplicado a proposito para probar FEFO.
    $itemA = seedItem($pdo, $deviceId, 'Vacunas A', UID_A, 10, '2027-01-01');
    $itemB1 = seedItem($pdo, $deviceId, 'Vacunas B lote viejo', UID_B, 4, '2026-09-01');
    $itemB2 = seedItem($pdo, $deviceId, 'Vacunas B lote nuevo', UID_B, 4, '2027-12-01');

    // --- Descarga: resta -----------------------------------------------------
    $t1 = time() - 300;
    $r = syncRfid($base, $secret, ++$seq, [['uid' => UID_A, 'movimiento' => 'descarga', 'time' => $t1]]);
    check('Sync con lectura HTTP 200', $r['status'] === 200, (string)$r['status']);
    $ev = $r['json']['ack']['rfid']['events'][0] ?? [];
    check('La lectura se aplico', ($ev['applied'] ?? false) === true, json_encode($ev));
    check('Descarga resta 1 (10 -> 9)', ($ev['quantity_before'] ?? null) === 10 && ($ev['quantity_after'] ?? null) === 9, json_encode($ev));
    check('La cantidad quedo persistida', qty($pdo, $itemA) === 9, (string)qty($pdo, $itemA));

    // --- Carga: suma ---------------------------------------------------------
    $r = syncRfid($base, $secret, ++$seq, [['uid' => UID_A, 'movimiento' => 'carga', 'time' => time() - 290]]);
    $ev = $r['json']['ack']['rfid']['events'][0] ?? [];
    check('Carga suma 1 (9 -> 10)', ($ev['quantity_after'] ?? null) === 10, json_encode($ev));

    // --- El firmware manda el entero del enum TipoRfid -----------------------
    $r = syncRfid($base, $secret, ++$seq, [['uid' => UID_A, 'movimiento' => 2, 'time' => time() - 280]]);
    $ev = $r['json']['ack']['rfid']['events'][0] ?? [];
    check('Acepta movimiento numerico (2 = descarga)', ($ev['direction'] ?? '') === 'out' && ($ev['quantity_after'] ?? null) === 9, json_encode($ev));

    // --- UID como lo imprime el firmware, con espacios -----------------------
    $r = syncRfid($base, $secret, ++$seq, [['uid' => '3A 5C 33 02', 'movimiento' => 'carga', 'time' => time() - 270]]);
    $ev = $r['json']['ack']['rfid']['events'][0] ?? [];
    check('Normaliza "3A 5C 33 02" a "3A:5C:33:02"', ($ev['uid'] ?? '') === UID_A && ($ev['applied'] ?? false) === true, json_encode($ev));

    // --- Idempotencia: el mismo escaneo reenviado no descuenta dos veces -----
    $tDup = time() - 260;
    $lectura = [['uid' => UID_A, 'movimiento' => 'descarga', 'time' => $tDup]];
    $r1 = syncRfid($base, $secret, ++$seq, $lectura);
    $qtyTrasPrimera = qty($pdo, $itemA);
    $r2 = syncRfid($base, $secret, ++$seq, $lectura); // mismo evento, otro packet_id
    $ev2 = $r2['json']['ack']['rfid']['events'][0] ?? [];
    check('El reenvio se marca como duplicado', ($ev2['duplicate'] ?? false) === true, json_encode($ev2));
    check('El reenvio NO vuelve a descontar', qty($pdo, $itemA) === $qtyTrasPrimera, qty($pdo, $itemA) . ' vs ' . $qtyTrasPrimera);

    // --- No baja de cero -----------------------------------------------------
    $pdo->prepare('UPDATE stock_items SET quantity = 1 WHERE id = ?')->execute([$itemA]);
    syncRfid($base, $secret, ++$seq, [['uid' => UID_A, 'movimiento' => 'descarga', 'time' => time() - 250]]);
    syncRfid($base, $secret, ++$seq, [['uid' => UID_A, 'movimiento' => 'descarga', 'time' => time() - 240]]);
    check('La cantidad no queda negativa', qty($pdo, $itemA) === 0, (string)qty($pdo, $itemA));

    // --- Cantidad mayor a 1 --------------------------------------------------
    $r = syncRfid($base, $secret, ++$seq, [['uid' => UID_A, 'movimiento' => 'carga', 'cantidad' => 5, 'time' => time() - 230]]);
    $ev = $r['json']['ack']['rfid']['events'][0] ?? [];
    check('Respeta cantidad > 1 (0 -> 5)', ($ev['quantity_after'] ?? null) === 5, json_encode($ev));

    // --- FEFO ante UID duplicado --------------------------------------------
    $r = syncRfid($base, $secret, ++$seq, [['uid' => UID_B, 'movimiento' => 'descarga', 'time' => time() - 220]]);
    $ev = $r['json']['ack']['rfid']['events'][0] ?? [];
    check('UID duplicado se marca ambiguous', ($ev['status'] ?? '') === 'ambiguous', json_encode($ev));
    check('Descuenta del lote que vence antes', qty($pdo, $itemB1) === 3 && qty($pdo, $itemB2) === 4, qty($pdo, $itemB1) . '/' . qty($pdo, $itemB2));

    // --- Tarjeta desconocida -------------------------------------------------
    $r = syncRfid($base, $secret, ++$seq, [['uid' => UID_DESCONOCIDO, 'movimiento' => 'descarga', 'time' => time() - 210]]);
    $ev = $r['json']['ack']['rfid']['events'][0] ?? [];
    check('Tarjeta desconocida: status unmatched', ($ev['status'] ?? '') === 'unmatched', json_encode($ev));
    check('Tarjeta desconocida no rompe el sync', $r['status'] === 200);
    $unmatched = (int)$pdo->query("SELECT COUNT(*) FROM stock_movements WHERE device_id = {$deviceId} AND status = 'unmatched'")->fetchColumn();
    check('Queda asentada para reconciliar', $unmatched === 1, (string)$unmatched);

    // --- Entradas invalidas no tumban el paquete -----------------------------
    $r = syncRfid($base, $secret, ++$seq, [
        ['uid' => 'no valido!!', 'movimiento' => 'carga', 'time' => time() - 200],
        ['uid' => UID_A, 'movimiento' => 'zaraza', 'time' => time() - 199],
        ['uid' => UID_A, 'movimiento' => 'carga', 'cantidad' => 0, 'time' => time() - 198],
        ['uid' => UID_A, 'movimiento' => 'carga', 'time' => time() - 197],
    ]);
    $eventos = $r['json']['ack']['rfid']['events'] ?? [];
    check('Sync con lecturas invalidas sigue siendo 200', $r['status'] === 200);
    check('Reporta 3 invalidas', count(array_filter($eventos, fn($e) => ($e['status'] ?? '') === 'invalid')) === 3, json_encode($eventos));
    check('La lectura valida del mismo lote se aplica', ($r['json']['ack']['rfid']['applied'] ?? 0) === 1, json_encode($r['json']['ack']['rfid'] ?? []));

    // --- Las temperaturas del mismo paquete siguen funcionando ---------------
    $ts = time();
    $r = syncFull($base, $secret, ++$seq, [['temp' => 5.1, 'time' => $ts - 60]], [['uid' => UID_A, 'movimiento' => 'descarga', 'time' => $ts - 55]]);
    check('Temperatura y RFID conviven en el mismo sync', ($r['json']['ack']['inserted'] ?? 0) === 1 && ($r['json']['ack']['rfid']['applied'] ?? 0) === 1, json_encode($r['json']['ack'] ?? []));

    // --- Endpoints de consulta ----------------------------------------------
    $token = login($base, $pdo);
    $mov = get($base . "/devices/{$deviceId}/movements", $token);
    check('GET /movements HTTP 200', $mov['status'] === 200, (string)$mov['status']);
    check('Devuelve el historial', count($mov['json']['data'] ?? []) >= 8, (string)count($mov['json']['data'] ?? []));

    $lookup = get($base . "/devices/{$deviceId}/stock?rfid=" . urlencode(UID_B), $token);
    check('Busqueda por RFID HTTP 200', $lookup['status'] === 200);
    check('Devuelve los 2 items del UID duplicado', count($lookup['json']['data'] ?? []) === 2, (string)count($lookup['json']['data'] ?? []));

    $vacio = get($base . "/devices/{$deviceId}/stock?rfid=" . urlencode(UID_DESCONOCIDO), $token);
    check('Busqueda de tarjeta inexistente devuelve lista vacia', ($vacio['status'] === 200) && count($vacio['json']['data'] ?? []) === 0);

    $malformado = get($base . "/devices/{$deviceId}/stock?rfid=" . urlencode('no valido!!'), $token);
    check('Busqueda con UID invalido devuelve 400', $malformado['status'] === 400, (string)$malformado['status']);
} finally {
    cleanup($pdo);
}

echo "\nRESULTADO: {$pass} OK / {$fail} FAIL\n";
exit($fail === 0 ? 0 : 1);

// ---------------------------------------------------------------------------

function check(string $label, bool $cond, string $extra = ''): void
{
    global $pass, $fail;
    if ($cond) { $pass++; echo "[OK] {$label}\n"; }
    else { $fail++; echo "[FAIL] {$label}  {$extra}\n"; }
}

function qty(PDO $pdo, int $itemId): int
{
    $stmt = $pdo->prepare('SELECT quantity FROM stock_items WHERE id = ?');
    $stmt->execute([$itemId]);
    return (int)$stmt->fetchColumn();
}

function seedItem(PDO $pdo, int $deviceId, string $name, string $rfid, int $qty, string $exp): int
{
    $pdo->prepare('INSERT INTO stock_items (device_id, name, rfid, quantity, expiration_date) VALUES (?, ?, ?, ?, ?)')
        ->execute([$deviceId, $name, $rfid, $qty, $exp]);
    return (int)$pdo->lastInsertId();
}

function canon($v)
{
    if (!is_array($v)) return $v;
    if (array_keys($v) === range(0, count($v) - 1)) return array_map('canon', $v);
    ksort($v);
    foreach ($v as $k => $x) $v[$k] = canon($x);
    return $v;
}

function signedBody(array $payload, string $secret): array
{
    $forSig = $payload;
    unset($forSig['mac'], $forSig['timestamp'], $forSig['signature']);
    $json = json_encode(canon($forSig), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $payload['signature'] = hash_hmac('sha256', $payload['mac'] . $payload['timestamp'] . $json, $secret);
    return $payload;
}

function syncRfid(string $base, string $secret, int $seq, array $events): array
{
    return syncFull($base, $secret, $seq, [], $events);
}

function syncFull(string $base, string $secret, int $seq, array $data, array $events): array
{
    $ts = time();
    $payload = signedBody([
        'mac' => RFID_MAC,
        'timestamp' => $ts,
        'packet_id' => "rfid-test-{$ts}-{$seq}",
        'seq' => $seq,
        'data' => $data,
        'local_alerts' => [],
        'rfid_events' => $events,
    ], $secret);

    return post($base . '/esp/sync', $payload);
}

function login(string $base, PDO $pdo): string
{
    $r = post($base . '/login', ['identifier' => 'devtest', 'password' => 'Test1234']);
    return $r['json']['data']['token'] ?? '';
}

function post(string $url, array $payload): array
{
    return request('POST', $url, $payload, null);
}

function get(string $url, ?string $token): array
{
    return request('GET', $url, null, $token);
}

function request(string $method, string $url, ?array $payload, ?string $token): array
{
    $ch = curl_init($url);
    $headers = ['Content-Type: application/json'];
    if ($token) $headers[] = 'Authorization: Bearer ' . $token;
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['status' => $status, 'json' => json_decode((string)$raw, true)];
}

function loadEnv(string $path): array
{
    $env = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim($v);
    }
    return $env;
}

function connectDb(array $env): PDO
{
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $env['DB_HOST'] ?? 'localhost', $env['DB_NAME'] ?? 'temp_segura');
    return new PDO($dsn, $env['DB_USER'] ?? 'root', $env['DB_PASS'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

function cleanup(PDO $pdo): void
{
    $pdo->exec("DELETE FROM rate_limit_events WHERE bucket LIKE 'esp-%'");
    $stmt = $pdo->prepare('SELECT id FROM devices WHERE mac_address = ?');
    $stmt->execute([RFID_MAC]);
    $id = $stmt->fetchColumn();
    if ($id !== false) {
        // stock_movements y stock_items caen por FK ON DELETE CASCADE.
        $pdo->prepare('DELETE FROM devices WHERE id = ?')->execute([$id]);
    }
}
