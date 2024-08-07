<?php

// Desarrollado por Paulo Contrera - www.paulo-contrera.com para SygSeguros

    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: X-API-KEY, Origin,  Content-Type, Accept, Access-Control-Request-Method");

    require_once "../models/Cliente.php";

    if(isset($_GET['id'])) {
    $id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    if ($id !== false) {
        echo json_encode(Cliente::getWhere($id));
    } else {
        echo json_encode(["error" => "ID inválido"]);
    }
} else {
    echo json_encode(Cliente::getAll());
}

?>