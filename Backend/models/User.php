<?php

namespace Models;

use Helpers\AuditLogger;
use PDO;

class User
{
    private $db;
    private $table = 'users';

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function exists($username, $email)
    {
        $sql = "SELECT id FROM {$this->table} WHERE username = :username OR email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['username' => $username, 'email' => $email]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
    }

    public function create($name, $username, $password, $email, $phone)
    {
        $sql = "INSERT INTO {$this->table} (name, username, password, email, phone, is_email_verified, registered_at)
            VALUES (:name, :username, :password, :email, :phone, 0, NOW())";
        $stmt = $this->db->prepare($sql);

        $success = $stmt->execute([
            'name' => $name,
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'phone' => $phone
        ]);

        return $success ? $this->db->lastInsertId() : false;
    }

    public function createManaged($name, $username, $password, $email, $phone, $role, bool $verified = true)
    {
        $sql = "INSERT INTO {$this->table} (
                name, username, password, email, phone, role, is_email_verified, registered_at
            ) VALUES (
                :name, :username, :password, :email, :phone, :role, :verified, NOW()
            )";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            'name' => $name,
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'phone' => $phone,
            'role' => $role,
            'verified' => $verified ? 1 : 0,
        ]);

        return $success ? $this->db->lastInsertId() : false;
    }

    public function createVerifiedEmailRecord($userId, $email, $ip): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO email_verifications (user_id, email, token, expires_at, verified, verified_at, ip_address)
            VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 YEAR), 1, NOW(), ?)
        ");

        return $stmt->execute([
            $userId,
            $email,
            bin2hex(random_bytes(16)),
            $ip,
        ]);
    }


    public function findByUsernameOrEmail($identifier)
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :username OR email = :email LIMIT 1');
        $stmt->execute(['username' => $identifier, 'email' => $identifier]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function incrementFailedAttempts($userId)
    {
        $stmt = $this->db->prepare('UPDATE users SET failed_login_attempts = failed_login_attempts + 1 WHERE id = :id');
        $stmt->execute(['id' => $userId]);
    }

    public function resetFailedAttempts($userId)
    {
        $stmt = $this->db->prepare('UPDATE users SET failed_login_attempts = 0, last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $userId]);
    }

    public function getAllUsers()
    {
        $stmt = $this->db->query('
            SELECT id, name, username, email, phone, role, is_email_verified, last_login_at, registered_at, updated_at
            FROM users
            ORDER BY registered_at DESC, id DESC
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserById($id)
    {
        $stmt = $this->db->prepare('
            SELECT id, name, username, email, phone, role, is_email_verified, last_login_at, registered_at, updated_at
            FROM users
            WHERE id = ?
        ');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getUserByIdWithPassword($id)
    {
        $stmt = $this->db->prepare('SELECT id, name, username, email, phone, role, password FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateUser($id, $name, $email, $phone, $changedBy)
    {
        // Verificar si el usuario existe
        $stmt = $this->db->prepare("SELECT name, email, phone FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $existingUser = $stmt->fetch();

        if (!$existingUser) {
            return ['success' => false, 'reason' => 'not_found'];
        }

        // Verificar si el email ya existe en otro usuario
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
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

        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute($params);

        return $success ? ['success' => true] : ['success' => false, 'reason' => 'db_error'];
    }

    public function deleteUser($id, $changedBy)
    {
        // Verificamos si el usuario existe
        $stmt = $this->db->prepare("SELECT id, name, username, email, phone, role FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if (!$user) {
            return 'not_found';
        }

        // Registrar el log ANTES de eliminar
        $this->logChange($id, $changedBy, 'deleted', json_encode($user), null);

        // Eliminar el usuario
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        $success = $stmt->execute([$id]);

        return $success;
    }
    public function findByEmail($email)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function changePassword($id, $currentPassword, $newPassword, $changedBy)
    {
        // Obtener usuario
        $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
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
        $stmt = $this->db->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $success = $stmt->execute([$hashedPassword, $id]);

        if ($success) {
            $this->logChange($id, $changedBy, 'password', '***', '***');
            return ['success' => true];
        } else {
            return ['success' => false, 'reason' => 'db_error'];
        }
    }

    public function emailExistsForAnother($email, $id): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $stmt->execute([$email, $id]);
        return (bool)$stmt->fetch();
    }

    public function usernameExistsForAnother($username, $id): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1");
        $stmt->execute([$username, $id]);
        return (bool)$stmt->fetch();
    }

    public function changeRole($id, $newRole, $changedBy)
    {
        $stmt = $this->db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'reason' => 'not_found'];
        }

        if ($user['role'] === $newRole) {
            return ['success' => false, 'reason' => 'no_changes'];
        }

        $stmt = $this->db->prepare("UPDATE users SET role = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $success = $stmt->execute([$newRole, $id]);

        if ($success) {
            $this->logChange($id, $changedBy, 'role', $user['role'], $newRole);
            return ['success' => true];
        }

        return ['success' => false, 'reason' => 'db_error'];
    }

    public function setPasswordByAdmin($id, $newPassword, $changedBy)
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'reason' => 'not_found'];
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password = ?, failed_login_attempts = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $success = $stmt->execute([$hashedPassword, $id]);

        if ($success) {
            $this->logChange($id, $changedBy, 'password_admin_reset', '***', '***');
            return ['success' => true];
        }

        return ['success' => false, 'reason' => 'db_error'];
    }

    public function changeUsername($id, $newUsername, $changedBy)
    {
        // Verificar si el nuevo username está en uso
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$newUsername, $id]);
        if ($stmt->fetch()) {
            return ['success' => false, 'reason' => 'duplicate_username'];
        }

        // Obtener el username actual
        $stmt = $this->db->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'reason' => 'not_found'];
        }

        // Actualizar username
        $stmt = $this->db->prepare("UPDATE users SET username = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $success = $stmt->execute([$newUsername, $id]);

        if ($success) {
            $this->logChange($id, $changedBy, 'username', $user['username'], $newUsername);
            return ['success' => true];
        } else {
            return ['success' => false, 'reason' => 'db_error'];
        }
    }

    public function getByVerificationToken($token)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email_verification_token = ?");
        $stmt->execute([$token]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function verifyEmail($userId)
    {
        $stmt = $this->db->prepare("UPDATE users SET is_email_verified = 1 WHERE id = ?");
        return $stmt->execute([$userId]);
    }

    public function createPasswordResetRequest($userId, $email, $token, $expiresAt, $ip)
    {
        $sql = "INSERT INTO password_resets (user_id, email, token, expires_at, ip_address)
            VALUES (:user_id, :email, :token, :expires_at, :ip)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'user_id' => $userId,
            'email' => $email,
            'token' => $token,
            'expires_at' => $expiresAt,
            'ip' => $ip
        ]);
    }

    public function findValidPasswordResetToken($token)
    {
        $sql = "SELECT * FROM password_resets
            WHERE token = :token AND expires_at > NOW() AND used = 0
            LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function findValidPasswordResetTokeforLog($token)
    {
        $sql = "SELECT * FROM password_resets
            WHERE token = :token AND expires_at > NOW()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function invalidatePreviousPasswordResetTokens($userId)
    {
        $sql = "UPDATE password_resets
            SET used = 1
            WHERE user_id = :user_id AND used = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
    }

    public function resetPasswordWithToken($token, $newHashedPassword)
    {
        $this->db->beginTransaction();
        try {
            $reset = $this->findValidPasswordResetToken($token);

            if (!$reset) {
                $this->db->rollBack();
                return ['success' => false, 'reason' => 'invalid_token'];
            }

            // Actualizar contraseña
            $stmt = $this->db->prepare("UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id");
            $stmt->execute([
                'password' => $newHashedPassword,
                'id' => $reset['user_id']
            ]);

            // Marcar como usado
            $stmt = $this->db->prepare("UPDATE password_resets SET used = 1 WHERE id = :id");
            $stmt->execute(['id' => $reset['id']]);

            $this->db->commit();
            return ['success' => true];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'reason' => 'internal_error'];
        }
    }

    public function logChange($userId, $changedBy, $fieldChanged, $oldValue, $newValue)
    {
        $stmt = $this->db->prepare("INSERT INTO user_change_log (user_id, field_changed, old_value, new_value, changed_by) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$userId, $fieldChanged, $oldValue, $newValue, $changedBy]);

        AuditLogger::event('user_change', 'Cambio de usuario', 'info', [
            'target_user_id' => $userId,
            'field_changed' => $fieldChanged,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ], $changedBy ? (int)$changedBy : null, 'user', (string)$userId, 'update');

        return $result;
    }

    public function logEvent($userId, $eventType, $message, $ip)
    {
        $stmt = $this->db->prepare("
        INSERT INTO event_logs (user_id, event_type, event_message, ip_address)
        VALUES (:user_id, :event_type, :message, :ip)");
        $result = $stmt->execute([
            'user_id' => $userId,
            'event_type' => $eventType,
            'message' => $message,
            'ip' => $ip
        ]);

        $severity = str_contains((string)$eventType, 'failed') || str_contains((string)$eventType, 'fail') || str_contains((string)$eventType, 'blocked')
            ? 'warning'
            : 'info';
        AuditLogger::event('auth_event', $message, $severity, [
            'event_type' => $eventType,
            'legacy_ip' => $ip,
        ], $userId ? (int)$userId : null, 'user', $userId ? (string)$userId : null, $eventType);

        return $result;
    }
}
