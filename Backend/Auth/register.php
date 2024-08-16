<?php

// Desarrollado por Paulo Contrera https://paulo-contrera.web.app/

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, Content-Type, Accept, Access-Control-Request-Method");

require_once '../models/Register.php';

$datos = json_decode(file_get_contents('php://input'));

if ($datos !== null && isset($datos->username) && isset($datos->password) && isset($datos->email) && isset($datos->role)) {
    $username = filter_var($datos->username, FILTER_SANITIZE_STRING);
    $password = filter_var($datos->password, FILTER_SANITIZE_STRING);
    $email = filter_var($datos->email, FILTER_VALIDATE_EMAIL);
    $phone_number = isset($datos->phone_number) ? filter_var($datos->phone_number, FILTER_SANITIZE_STRING) : null;
    $role = filter_var($datos->role, FILTER_SANITIZE_STRING);
    
    if ($email) {
        echo Register::createUser($username, $password, $email, $phone_number, $role);
    } else {
        echo ResponseHelper::error('Email inválido.');
    }
} else {
    echo ResponseHelper::error('Datos insuficientes.');
}

?>
