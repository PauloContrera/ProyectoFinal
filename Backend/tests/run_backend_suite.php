<?php

declare(strict_types=1);

$backendBaseUrl = getenv('BACKEND_TEST_BASE_URL') ?: 'http://127.0.0.1:8000/api';
putenv('BACKEND_TEST_BASE_URL=' . $backendBaseUrl);

if (!getenv('ESP_TEST_BASE_URL')) {
    putenv('ESP_TEST_BASE_URL=' . $backendBaseUrl);
}

$php = PHP_BINARY ?: 'php';
$tests = [
    'access_control_security_test.php' => 'Seguridad ACL/IDOR: aislamiento por usuario, admin editable y visitor solo lectura',
    'backend_roles_flow_test.php' => 'Usuarios, roles, permisos, stock, temperaturas y auditoria',
    'protocol_http_sms_test.php' => 'ESP32: registro, firma HMAC, sync idempotente y comandos',
    'alert_generation_test.php' => 'Alertas: evaluacion server-side, cooldown, acknowledge y alarmero',
    'security_hardening_test.php' => 'Bloqueo temporal de cuenta, anti-enumeracion y provisioning ESP por dispositivo',
    'rfid_stock_movements_test.php' => 'RFID: entradas y salidas de stock, idempotencia y reconciliacion',
];

foreach ($tests as $testFile => $description) {
    $path = __DIR__ . DIRECTORY_SEPARATOR . $testFile;
    echo "\n============================================================\n";
    echo "Ejecutando: {$description}\n";
    echo "Archivo: {$testFile}\n";
    echo "Base URL: {$backendBaseUrl}\n";
    echo "============================================================\n";

    $command = escapeshellarg($php) . ' ' . escapeshellarg($path);
    passthru($command, $exitCode);

    if ($exitCode !== 0) {
        fwrite(STDERR, "\nSuite detenida: {$testFile} fallo con codigo {$exitCode}.\n");
        exit($exitCode);
    }
}

echo "\nOK suite backend completa.\n";
