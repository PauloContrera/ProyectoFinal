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

public function updateUser($id, $name, $email, $phone, $changedBy)
{
    // Verificar si el usuario existe
    $stmt = $this->conn->prepare("SELECT name, email, phone FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $existingUser = $stmt->fetch();

    if (!$existingUser) {
        return ['success' => false, 'reason' => 'not_found'];
    }

    // Verificar si el email ya existe en otro usuario
    $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) {
        return ['success' => false, 'reason' => 'duplicate_email'];
    }

    // Comparar campos y registrar cambios
    $fieldsToUpdate = [];
    $params = [];

    if ($name !== $existingUser['name']) {
        $fieldsToUpdate[] = "name = ?";
        $params[] = $name;
        $this->logChange($id, $changedBy, 'name', $existingUser['name'], $name);
    }

    if ($email !== $existingUser['email']) {
        $fieldsToUpdate[] = "email = ?";
        $params[] = $email;
        $this->logChange($id, $changedBy, 'email', $existingUser['email'], $email);
    }

    if ($phone !== $existingUser['phone']) {
        $fieldsToUpdate[] = "phone = ?";
        $params[] = $phone;
        $this->logChange($id, $changedBy, 'phone', $existingUser['phone'], $phone);
    }

    if (empty($fieldsToUpdate)) {
        return ['success' => false, 'reason' => 'no_changes'];
    }

    // Ejecutar la actualización
    $sql = "UPDATE users SET " . implode(', ', $fieldsToUpdate) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $params[] = $id;

    $stmt = $this->conn->prepare($sql);
    $success = $stmt->execute($params);

    return $success ? ['success' => true] : ['success' => false, 'reason' => 'db_error'];
}



public function deleteUser($id)
{
    // Verificamos si el usuario existe
    $stmt = $this->conn->prepare("SELECT id, name, username, email, phone, role FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        return 'not_found';
    }

    // Registrar el log ANTES de eliminar
    $changedBy = $_SERVER['user']['id'];
    $this->logChange($id, $changedBy, 'deleted', json_encode($user), null);

    // Eliminar el usuario
    $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
    $success = $stmt->execute([$id]);

    return $success;
}

public function changePassword($id, $currentPassword, $newPassword, $changedBy)
{
    // Obtener usuario
    $stmt = $this->conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'reason' => 'not_found'];
    }

    // Validar contraseña actual
    if (!password_verify($currentPassword, $user['password'])) {
        return ['success' => false, 'reason' => 'invalid_password'];
    }

    // Hashear nueva contraseña
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Actualizar contraseña
    $stmt = $this->conn->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $success = $stmt->execute([$hashedPassword, $id]);

    if ($success) {
        $this->logChange($id, $changedBy, 'password', '***', '***');
        return ['success' => true];
    } else {
        return ['success' => false, 'reason' => 'db_error'];
    }
}
public function changeUsername($id, $newUsername, $changedBy)
{
    // Verificar si el nuevo username está en uso
    $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$newUsername, $id]);
    if ($stmt->fetch()) {
        return ['success' => false, 'reason' => 'duplicate_username'];
    }

    // Obtener el username actual
    $stmt = $this->conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'reason' => 'not_found'];
    }

    // Actualizar username
    $stmt = $this->conn->prepare("UPDATE users SET username = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $success = $stmt->execute([$newUsername, $id]);

    if ($success) {
        $this->logChange($id, $changedBy, 'username', $user['username'], $newUsername);
        return ['success' => true];
    } else {
        return ['success' => false, 'reason' => 'db_error'];
    }
}








public function logChange($userId, $changedBy, $fieldChanged, $oldValue, $newValue)
{
    $stmt = $this->conn->prepare("INSERT INTO user_change_log (user_id, field_changed, old_value, new_value, changed_by) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$userId, $fieldChanged, $oldValue, $newValue, $changedBy]);
}



}
