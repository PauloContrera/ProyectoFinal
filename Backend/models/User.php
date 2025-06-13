<?php

namespace Models;

use PDO;

class User
{
    private $conn;
    private $table = 'users';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function exists($username, $email)
    {
        $sql = "SELECT id FROM {$this->table} WHERE username = :username OR email = :email LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['username' => $username, 'email' => $email]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
    }

    public function create($name, $username, $password, $email, $phone)
    {
        $sql = "INSERT INTO {$this->table} (name, username, password, email, phone) 
                VALUES (:name, :username, :password, :email, :phone)";
        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            'name' => $name,
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'phone' => $phone
        ]);
    }

    public function findByUsernameOrEmail($identifier)
    {
        $stmt = $this->conn->prepare('SELECT * FROM users WHERE username = :identifier OR email = :identifier LIMIT 1');
        $stmt->execute(['identifier' => $identifier]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function incrementFailedAttempts($userId)
    {
        $stmt = $this->conn->prepare('UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE id = :id');
        $stmt->execute(['id' => $userId]);
    }

    public function resetFailedAttempts($userId)
    {
        $stmt = $this->conn->prepare('UPDATE users SET failed_login_attempts = 0, last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $userId]);
    }

    public function getAllUsers()
    {
        $stmt = $this->conn->query('SELECT id, name, username, email, phone, role FROM users');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserById($id)
    {
        $stmt = $this->conn->prepare('SELECT id, name, username, email, phone, role FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateUser($id, $name, $email, $phone)
    {
        // Verificar si el usuario existe
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $existingUser = $stmt->fetch();

        if (!$existingUser) {
            return ['success' => false, 'reason' => 'not_found'];
        }

        // Verificar si el email ya existe en otro usuario
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) {
            return ['success' => false, 'reason' => 'duplicate_email'];
        }

        // Ejecutar la actualización
        $stmt = $this->conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
        $success = $stmt->execute([$name, $email, $phone, $id]);

        return ['success' => $success, 'reason' => 'ok'];
    }

public function deleteUser($id)
{
    try {
        // Primero verificamos si el usuario existe
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return 'not_found';
        }

        // Registrar el log ANTES de eliminar
        $changedBy = $_SERVER['user']['id'];
        $logSuccess = $this->logChange($id, $changedBy, 'deleted', json_encode($user), null);

        // Si el log falla, no borramos
        if (!$logSuccess) {
            return 'log_failed';
        }

        // Eliminar el usuario
        // $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
        // $success = $stmt->execute([$id]);

        return $success;
    } catch (Exception $e) {
        return false;
    }
}

public function logChange($userId, $changedBy, $fieldChanged, $oldValue, $newValue)
{
    try {
        $stmt = $this->conn->prepare("INSERT INTO user_change_log (user_id, field_changed, old_value, new_value, changed_by) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$userId, $fieldChanged, $oldValue, $newValue, $changedBy]);
    } catch (Exception $e) {
        return false;
    }
}


}
