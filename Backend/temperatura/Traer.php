<?php

// Desarrollado por Paulo Contrera https://paulo-contrera.web.app/ 

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, Content-Type, Accept, Access-Control-Request-Method");

require_once "../models/Temperatura.php";

if (isset($_GET['id'])) {
    // Validar que el ID sea un número entero
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id !== false) {
        $result = Temperatura::getWhere($id);
        if (!empty($result)) {
            echo json_encode($result);
        } else {
            echo json_encode(["error" => "No se encontraron registros con el ID proporcionado"]);
        }
    } else {
        echo json_encode(["error" => "ID invalido"]);
    }

} elseif (isset($_GET['fridge_id'])) {
    // Validar que el ID de la heladera sea un número entero
    $fridge_id = filter_var($_GET['fridge_id'], FILTER_VALIDATE_INT);
    if ($fridge_id !== false) {
        $result = Temperatura::getAll($fridge_id);
        if (!empty($result)) {
            echo json_encode($result);
        } else {
            echo json_encode(["error" => "No se encontraron registros para la heladera con el ID proporcionado"]);
        }
    } else {
        echo json_encode(["error" => "Fridge ID invalido"]);
    }

} else {
    echo json_encode(["error" => "Parametros insuficientes"]);
}

?>
