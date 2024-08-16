<?php

require_once "../connection/Connection.php";
require_once '../utils/ResponseHelper.php';

class Register {

    public static function createUser($username, $password, $email, $phone_number, $role) {
        $db = new Connection();
        
        // Encriptar la contraseña
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Verificar si el email o el username ya están registrados
        $query = "SELECT id FROM users WHERE username=? OR email=?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return ResponseHelper::error('El usuario o el email ya estan registrados.');
        }

        // Insertar el nuevo usuario
        $query = "INSERT INTO users (username, password, email, phone_number, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bind_param("sssss", $username, $hashed_password, $email, $phone_number, $role);
        
        if ($stmt->execute()) {
            return ResponseHelper::success(['user_id' => $stmt->insert_id]);
        } else {
            return ResponseHelper::error('Error al registrar el usuario.');
        }
    }
}

?>
