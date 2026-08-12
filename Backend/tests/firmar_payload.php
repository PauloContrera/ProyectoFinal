<?php

/**
 * Calcula la firma HMAC del protocolo ESP y arma el body listo para pegar en
 * Postman. Sirve para la coleccion "sin scripts", donde la firma va a mano.
 *
 * USO
 *   php Backend/tests/firmar_payload.php <archivo.json> <shared_secret> [timestamp]
 *   php Backend/tests/firmar_payload.php --ejemplo=sync-rfid <shared_secret>
 *
 * El archivo JSON lleva 'mac' y los campos que se firman, SIN 'timestamp' ni
 * 'signature': los agrega este script. Si no se pasa timestamp usa la hora actual.
 *
 * Imprime el canonico, el mensaje firmado, la firma y el body completo.
 */

declare(strict_types=1);

$args = array_slice($argv, 1);
if (!$args) {
    fwrite(STDERR, ayuda());
    exit(1);
}

$ejemplos = [
    'sync-rfid' => [
        'mac' => 'A1:B2:C3:D4:E5:01',
        'packet_id' => 'manual-rfid-001',
        'seq' => 1,
        'data' => [],
        'local_alerts' => [],
        'rfid_events' => [
            ['uid' => '3A:5C:33:02', 'movimiento' => 'descarga', 'time' => time() - 30],
        ],
    ],
    'sync-completo' => [
        'mac' => 'A1:B2:C3:D4:E5:01',
        'packet_id' => 'manual-full-001',
        'seq' => 2,
        'data' => [
            ['temp' => 4.3, 'time' => time() - 60],
            ['temp' => 4.6, 'time' => time() - 30],
        ],
        'local_alerts' => [],
        'rfid_events' => [
            ['uid' => '3A:5C:33:02', 'movimiento' => 'carga', 'cantidad' => 5, 'time' => time() - 20],
        ],
        'optional' => ['uptime' => 86400, 'signal_strength' => -67, 'battery_level' => 89],
    ],
    'sync-temperatura' => [
        'mac' => 'A1:B2:C3:D4:E5:01',
        'packet_id' => 'manual-temp-001',
        'seq' => 3,
        'data' => [['temp' => 5.1, 'time' => time() - 60]],
        'local_alerts' => [],
    ],
    'command-response' => [
        'mac' => 'A1:B2:C3:D4:E5:01',
        'packet_id' => 'manual-cmd-001',
        'seq' => 4,
        'respuesta_comando' => [
            'tipo' => 'cambio_config',
            'estado' => 'ok',
            'detalle' => 'Parametros aplicados correctamente',
        ],
    ],
];

$payload = null;
$secret = null;
$timestamp = time();
$posicionales = [];

foreach ($args as $arg) {
    if (str_starts_with($arg, '--ejemplo=')) {
        $nombre = substr($arg, strlen('--ejemplo='));
        if (!isset($ejemplos[$nombre])) {
            fwrite(STDERR, "Ejemplo desconocido: {$nombre}\nDisponibles: " . implode(', ', array_keys($ejemplos)) . "\n");
            exit(1);
        }
        $payload = $ejemplos[$nombre];
        continue;
    }
    if ($arg === '--help' || $arg === '-h') {
        echo ayuda();
        exit(0);
    }
    $posicionales[] = $arg;
}

if ($payload === null) {
    $ruta = array_shift($posicionales);
    if ($ruta === null || !is_file($ruta)) {
        fwrite(STDERR, "No se encontro el archivo JSON: " . ($ruta ?? '(ninguno)') . "\n\n" . ayuda());
        exit(1);
    }
    $payload = json_decode((string)file_get_contents($ruta), true);
    if (!is_array($payload)) {
        fwrite(STDERR, "El archivo no es un JSON valido.\n");
        exit(1);
    }
}

$secret = array_shift($posicionales);
if (!$secret) {
    fwrite(STDERR, "Falta el shared_secret.\n\n" . ayuda());
    exit(1);
}

$tsArg = array_shift($posicionales);
if ($tsArg !== null) {
    $timestamp = (int)$tsArg;
}

if (empty($payload['mac'])) {
    fwrite(STDERR, "El payload debe incluir 'mac'.\n");
    exit(1);
}

$mac = normalizarMac((string)$payload['mac']);
if ($mac === null) {
    fwrite(STDERR, "MAC invalida.\n");
    exit(1);
}

// Se firma el payload sin mac, timestamp ni signature.
$firmado = $payload;
unset($firmado['mac'], $firmado['timestamp'], $firmado['signature']);

$canonico = json_encode(canonizar($firmado), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$mensaje = $mac . $timestamp . $canonico;
$firma = hash_hmac('sha256', $mensaje, $secret);

$completo = array_merge(['mac' => $mac, 'timestamp' => $timestamp], $firmado, ['signature' => $firma]);

$linea = str_repeat('=', 72);
echo "{$linea}\nJSON CANONICO (lo que se firma)\n{$linea}\n{$canonico}\n\n";
echo "{$linea}\nMENSAJE HMAC  (mac + timestamp + canonico)\n{$linea}\n{$mensaje}\n\n";
echo "{$linea}\nFIRMA\n{$linea}\n{$firma}\n\n";
echo "{$linea}\nBODY COMPLETO — pegar en Postman\n{$linea}\n";
echo json_encode($completo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n\n";

$vence = $timestamp + 900;
echo "Timestamp: {$timestamp}  (" . gmdate('Y-m-d H:i:s', $timestamp) . " UTC)\n";
echo "Valido hasta aproximadamente " . gmdate('H:i:s', $vence) . " UTC: la tolerancia por defecto son 900 s.\n";

function canonizar($valor)
{
    if (!is_array($valor)) {
        return $valor;
    }
    if (array_keys($valor) === range(0, count($valor) - 1)) {
        return array_map('canonizar', $valor);
    }
    ksort($valor);
    foreach ($valor as $clave => $item) {
        $valor[$clave] = canonizar($item);
    }
    return $valor;
}

function normalizarMac(string $mac): ?string
{
    $compacto = strtoupper((string)preg_replace('/[^A-Fa-f0-9]/', '', $mac));
    if (strlen($compacto) !== 12) {
        return null;
    }
    return implode(':', str_split($compacto, 2));
}

function ayuda(): string
{
    return <<<TXT
Firma un payload del protocolo ESP y arma el body listo para pegar en Postman.

  php Backend/tests/firmar_payload.php <archivo.json> <shared_secret> [timestamp]
  php Backend/tests/firmar_payload.php --ejemplo=<nombre> <shared_secret> [timestamp]

Ejemplos disponibles:
  sync-rfid          un sync con una lectura de descarga
  sync-completo      temperaturas + RFID + diagnostico
  sync-temperatura   solo temperatura, como el firmware actual
  command-response   confirmacion de un cambio de config

El archivo JSON lleva 'mac' y los campos a firmar, SIN 'timestamp' ni 'signature'.

Ejemplo:
  php Backend/tests/firmar_payload.php --ejemplo=sync-rfid a3f1b2c4...

TXT;
}
