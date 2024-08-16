<?php

// Desarrollado por Paulo Contrera https://paulo-contrera.web.app/ 

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-API-KEY, Origin, Content-Type, Accept, Access-Control-Request-Method');

require_once '../models/Temperatura.php';

// Leer y decodificar los datos JSON recibidos en el cuerpo de la solicitud
$datos = json_decode(file_get_contents('php://input'));

if ($datos !== null && isset($datos->fridge_id) && isset($datos->temperature)) {
    $fridge_id = filter_var($datos->fridge_id, FILTER_VALIDATE_INT);
    $temperature = filter_var($datos->temperature, FILTER_VALIDATE_FLOAT);
    
    if ($fridge_id !== false && $temperature !== false) {
        $result = Temperatura::insert($fridge_id, $temperature);
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Datos insuficientes']);
}


?>
