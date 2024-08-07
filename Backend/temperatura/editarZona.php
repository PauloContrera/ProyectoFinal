<?php

// Desarrollado por Paulo Contrera - www.paulo-contrera.com para SygSeguros

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: X-API-KEY, Origin,  Content-Type, Accept, Access-Control-Request-Method');
header('Access-Control-Allow-Methods: PUT');

require_once '../models/Cliente.php';

$datos = json_decode(file_get_contents('php://input'));

if ($datos) {
    if (Cliente::updateZona(
        $datos->id,
        $datos->Zona,
    )) {
        echo json_encode(['update' => true]);
    } else {
        echo json_encode(['update' => false]);
    }
} else {
    echo json_encode(['update' => false]);
}
