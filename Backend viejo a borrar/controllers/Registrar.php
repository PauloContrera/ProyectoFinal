<?php

// Desarrollado por Paulo Contrera https://paulo-contrera.web.app/ 

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-API-KEY, Origin, Content-Type, Accept, Access-Control-Request-Method');
header('Content-Type: application/json'); // Asegurar que la respuesta sea JSON

require_once '../models/Temperatura.php';

// Leer y decodificar los datos JSON recibidos en el cuerpo de la solicitud
// Leer y decodificar los datos JSON recibidos
// Leer el contenido JSON recibido
$inputJSON = file_get_contents('php://input');
file_put_contents("log.txt", "RAW INPUT: " . $inputJSON . "\n", FILE_APPEND); // Guardar en log

// Decodificar el JSON
$datos = json_decode($inputJSON, true);
file_put_contents("log.txt", "DECODED: " . print_r($datos, true) . "\n", FILE_APPEND); // Guardar en log

// Verificar si los datos existen y están bien estructurados
if ($datos !== null && isset($datos['fridge_id']) && isset($datos['data']) && is_array($datos['data'])) {
    $fridge_id = filter_var($datos['fridge_id'], FILTER_VALIDATE_INT);

    if ($fridge_id === false) {
        echo json_encode(['success' => false, 'error' => 'ID de heladera inválido']);
        exit();
    }

    $errores = [];
    $insertados = 0;

    foreach ($datos['data'] as $entry) {
        if (isset($entry['temperature']) && isset($entry['timestamps'])) {
            $temperature = filter_var($entry['temperature'], FILTER_VALIDATE_FLOAT);
            $timestamp = filter_var($entry['timestamps'], FILTER_SANITIZE_STRING);

            if ($temperature !== false && $timestamp) {
                $result = Temperatura::insert($fridge_id, $temperature, $timestamp);
                if ($result) {
                    $insertados++;
                } else {
                    $errores[] = ['temperature' => $entry['temperature'], 'timestamp' => $entry['timestamps'], 'error' => 'Error en la inserción'];
                }
            } else {
                $errores[] = ['temperature' => $entry['temperature'], 'timestamp' => $entry['timestamps'], 'error' => 'Datos inválidos'];
            }
        } else {
            $errores[] = ['error' => 'Faltan datos en una de las entradas'];
        }
    }

    if (empty($errores)) {
        echo json_encode(['success' => true, 'message' => "$insertados registros insertados correctamente"]);
    } else {
        echo json_encode(['success' => false, 'errores' => $errores]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Estructura de datos incorrecta', 'received_data' => $datos]);
}


?>
