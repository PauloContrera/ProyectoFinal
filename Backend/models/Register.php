<?php

require_once "../connection/Connection.php";
require_once '../utils/ResponseHelper.php';

class Register {

    public static function createUser($username, $password, $email, $phone_number, $role) {
        $db = new Connection();
        
        // Iniciar una transacción para asegurar que ambos procesos (creación de usuario y grupo) se completen juntos
        $db->begin_transaction();
        
        try {
            // Encriptar la contraseña
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Verificar si el email o el username ya están registrados
            $query = "SELECT id FROM users WHERE username=? OR email=?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                return ResponseHelper::error('El usuario o el email ya están registrados.');
            }

            // Insertar el nuevo usuario
            $query = "INSERT INTO users (username, password, email, phone_number, role) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->bind_param("sssss", $username, $hashed_password, $email, $phone_number, $role);

            if ($stmt->execute()) {
                $cliente_id = $stmt->insert_id;

                $query = "INSERT INTO fridge_groups(name, client_id) VALUES ('Default', ?)";
                $stmt = $db->prepare($query);
                $stmt->bind_param("i", $cliente_id);
                // Crear el grupo por defecto para el cliente
                if ($stmt->execute()) {
                    $db->commit(); // Confirmar la transacción si todo salió bien
                    return ResponseHelper::success(['user_id' => $cliente_id]);
                } else {
                    throw new Exception('Error al crear el grupo por defecto.');
                }
            } else {
                throw new Exception('Error al registrar el usuario.');
            }
        } catch (Exception $e) {
            // Si hay un error, revertir la transacción
            $db->rollback();
            return ResponseHelper::error($e->getMessage());
        }
    }

    private static function createDefaultGroup($cliente_id) {
        $db = new Connection();
        $query = "INSERT INTO fridge_groups(name, client_id) VALUES ('Default', ?)";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $cliente_id);

        return $stmt->execute();
    }
}

?>
