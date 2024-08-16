<?php

require_once '../models/Login.php';

$datos = json_decode(file_get_contents('php://input'));

if ($datos !== null) {
    $emailOrUsername = $datos->emailOrUsername;
    $password = $datos->password;
    
    echo Login::authenticate($emailOrUsername, $password);
} else {
    echo ResponseHelper::error('Datos no validos');
}

?>
