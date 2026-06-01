<?php

declare(strict_types=1);

$backendBaseUrl = getenv('BACKEND_TEST_BASE_URL') ?: 'http://127.0.0.1:8000/api';
putenv('BACKEND_TEST_BASE_URL=' . $backendBaseUrl);

if (!getenv('ESP_TEST_BASE_URL')) {
    putenv('ESP_TEST_BASE_URL=' . $backendBaseUrl);
}

$php = PHP_BINARY ?: 'php';
$tests = [
    'backend_roles_flow_test.php' => 'Usuarios, roles, permisos, stock, temperaturas y auditoria',
    'protocol_http_sms_test.php' => 'ESP32: registro, firma HMAC, sync idempotente y comandos',
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
