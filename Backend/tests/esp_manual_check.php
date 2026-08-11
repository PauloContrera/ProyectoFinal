<?php
// Simulador ESP manual contra el server en vivo. Usa el device seed DEV-G1-H1.
// Reproduce el contrato de firma documentado: HMAC_SHA256(mac + timestamp + json_canonico, secret)
declare(strict_types=1);

$base = getenv('ESP_TEST_BASE_URL') ?: 'http://127.0.0.1:8000/api';
$mac = 'A1:B2:C3:D4:E5:01';
$secret = 'local-dev-esp-secret';
$pass = 0; $fail = 0;
function check(string $label, bool $cond, $extra = '') {
    global $pass, $fail;
    if ($cond) { $pass++; echo "[OK] $label\n"; }
    else { $fail++; echo "[FAIL] $label  $extra\n"; }
}

// Canonicalizacion identica al backend: objetos ordenados por clave, arrays en orden.
function canon($v) {
    if (!is_array($v)) return $v;
    $isList = array_keys($v) === range(0, count($v) - 1);
    if ($isList) return array_map('canon', $v);
    ksort($v);
    foreach ($v as $k => $x) $v[$k] = canon($x);
    return $v;
}
function jsonData(array $payload): string {
    unset($payload['mac'], $payload['timestamp'], $payload['signature']);
    return json_encode(canon($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
function sign(array $payload, string $mac, int $ts, string $secret): string {
    return hash_hmac('sha256', $mac . $ts . jsonData($payload), $secret);
}
function post(string $url, array $body): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['http' => $code, 'json' => json_decode($raw, true)];
}
function getj(string $url): array {
    $raw = file_get_contents($url);
    return json_decode($raw, true) ?: [];
}

// 1) Hora del servidor
$time = getj($base . '/esp/time');
check('GET /esp/time success', ($time['success'] ?? false) === true);
$ts = (int)($time['server_time'] ?? time());

// 2) Sync firmado valido
$packetId = 'sync-manual-' . $ts;
$payload = [
    'mac' => $mac,
    'timestamp' => $ts,
    'packet_id' => $packetId,
    'seq' => 1,
    'data' => [
        ['temp' => 4.7, 'time' => $ts - 30],
        ['temp' => 5.1, 'time' => $ts - 10],
    ],
    'local_alerts' => [
        ['type' => 'temp_high', 'temp' => 10.2, 'time' => $ts - 5],
    ],
    'optional' => ['battery_level' => 88, 'signal_strength' => -67, 'uptime' => 12345],
];
$payload['signature'] = sign($payload, $mac, $ts, $secret);
$r = post($base . '/esp/sync', $payload);
check('Sync firmado HTTP 200', $r['http'] === 200, 'http=' . $r['http']);
check('Sync success=true', ($r['json']['success'] ?? false) === true, json_encode($r['json']['error'] ?? null));
check('Sync ACK accepted', ($r['json']['ack']['status'] ?? '') === 'accepted');
check('Sync NO filtra palabra_clave', !array_key_exists('palabra_clave', $r['json']));
check('Sync NO filtra shared_secret', !array_key_exists('shared_secret', $r['json']));
check('Sync incluye policy.max_batch_size', isset($r['json']['policy']['max_batch_size']));

// 3) Reintento idempotente (mismo payload exacto)
$r2 = post($base . '/esp/sync', $payload);
check('Sync duplicado HTTP 200', $r2['http'] === 200);
check('Sync duplicado status=duplicate', ($r2['json']['ack']['status'] ?? '') === 'duplicate');

// 4) Firma invalida
$bad = $payload;
$bad['packet_id'] = 'sync-bad-' . $ts;
$bad['signature'] = 'deadbeef';
$r3 = post($base . '/esp/sync', $bad);
check('Firma invalida HTTP 401', $r3['http'] === 401, 'http=' . $r3['http']);
check('Firma invalida ERR_FIRMA', ($r3['json']['error']['code'] ?? '') === 'ERR_FIRMA');

// 5) Replay conflictivo (mismo packet_id, distinto contenido)
$conflict = $payload;
$conflict['data'][0]['temp'] = 1.1; // cambia contenido, mismo packet_id
$conflict['signature'] = sign($conflict, $mac, $ts, $secret);
$r4 = post($base . '/esp/sync', $conflict);
check('Replay conflictivo HTTP 409', $r4['http'] === 409, 'http=' . $r4['http']);
check('Replay conflictivo ERR_REPLAY', ($r4['json']['error']['code'] ?? '') === 'ERR_REPLAY');

// 6) Command-response firmado
$cmd = [
    'mac' => $mac,
    'timestamp' => $ts,
    'packet_id' => 'cmd-manual-' . $ts,
    'seq' => 2,
    'respuesta_comando' => ['tipo' => 'cambio_config', 'estado' => 'ok', 'detalle' => 'Aplicado'],
];
$cmd['signature'] = sign($cmd, $mac, $ts, $secret);
$r5 = post($base . '/esp/command-response', $cmd);
check('Command-response HTTP 200', $r5['http'] === 200, 'http=' . $r5['http']);
check('Command-response success=true', ($r5['json']['success'] ?? false) === true);

echo "\nRESULTADO: $pass OK / $fail FAIL\n";
exit($fail === 0 ? 0 : 1);
