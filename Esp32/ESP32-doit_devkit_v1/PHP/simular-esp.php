<?php
/**
 * Simulador ESP por línea de comandos — Temp Segura
 *
 * Calcula el HMAC y manda una lectura firmada al servidor, igual que el firmware.
 *
 * USO:
 *   php simular-esp.php            -> manda temp 8.5
 *   php simular-esp.php 12.3       -> manda temp 12.3
 *   php simular-esp.php 25 nosend  -> solo muestra hash + body (no envía)
 *
 * Editá SHARED_SECRET con el valor COMPLETO (64 hex) del dispositivo en la DB.
 * (También se puede sobreescribir por variables de entorno ESP_BASE/ESP_MAC/ESP_SECRET.)
 */

// ===== CONFIG =====
$BASE   = getenv('ESP_BASE')   ?: 'https://tempsegura.orbitar.dev/api';
$MAC    = getenv('ESP_MAC')    ?: '4C:11:AE:70:26:70';
$SECRET = getenv('ESP_SECRET') ?: 'b45f063b9605742450c78649b78e18aca47e9b4fc23571e60e046fa3853f0462';
// ==================

$temp   = isset($argv[1]) ? (float)$argv[1] : 8.5;
$noSend = isset($argv[2]) && $argv[2] === 'nosend';
$ts     = time();

if ($SECRET === 'PEGAR_SECRET_COMPLETO_64_HEX') {
    fwrite(STDERR, "⚠️  Falta el shared_secret. Editá la línea SHARED_SECRET del script con el valor completo del dispositivo.\n");
    exit(1);
}

// Canonicalización idéntica al backend: claves de objetos ordenadas (recursivo); arrays en orden.
function canon($v) {
    if (is_array($v)) {
        $isList = array_keys($v) === range(0, count($v) - 1);
        if ($isList) return array_map('canon', $v);
        ksort($v, SORT_STRING);
        foreach ($v as $k => $x) $v[$k] = canon($x);
        return $v;
    }
    return $v;
}

$data = [
    'packet_id'    => 'pkt-' . $ts,
    'seq'          => 1,
    'data'         => [['temp' => $temp, 'time' => $ts]],
    'local_alerts' => [],
    'optional'     => ['firmware_version' => '1.0.0', 'rssi' => -60],
];

$jsonData  = json_encode(canon($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$signature = hash_hmac('sha256', $MAC . $ts . $jsonData, $SECRET);
$body      = json_encode(array_merge(
    ['mac' => $MAC, 'timestamp' => $ts, 'signature' => $signature],
    $data
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

echo "timestamp : $ts\n";
echo "signature : $signature\n";
echo "body      : $body\n\n";

if ($noSend) {
    echo "curl listo:\n";
    echo "curl -X POST \"$BASE/esp/sync\" -H \"Content-Type: application/json\" -d '$body'\n";
    exit(0);
}

$ch = curl_init("$BASE/esp/sync");
curl_setopt_array($ch, [
    CURLOPT_POST           => 1,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_TIMEOUT        => 25,
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "===> HTTP $code\n$resp\n";
