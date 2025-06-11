<?php

require_once __DIR__ . '/../connection/connection.php';

class UserModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function emailExiste($email): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function crearUsuario($name, $email, $phone, $hashedPassword, $role = 'cliente'): ?int
    {
        $stmt = $this->db->prepare("
            INSERT INTO users (name, email, phone, password, role)
            VALUES (:name, :email, :phone, :password, :role)
        ");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':role', $role);

        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }

        return null;
    }

    public function crearGrupoPorDefecto($clientId): bool
    {
        $nombreGrupo = "Grupo por defecto";
        $descripcion = "Grupo automático creado para el usuario";

        $stmt = $this->db->prepare("
            INSERT INTO fridge_groups (client_id, name, description)
            VALUES (:client_id, :name, :description)
        ");
        $stmt->bindParam(':client_id', $clientId);
        $stmt->bindParam(':name', $nombreGrupo);
        $stmt->bindParam(':description', $descripcion);

        return $stmt->execute();
    }

    public function eliminarUsuario($userId): void
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
    }
}
