<?php

namespace Controllers;

use Helpers\Validator;
use Helpers\Response;
use Helpers\TokenHelper;
use Models\User;
use Config\Database;
use Firebase\JWT\JWT;
use MailTemplates\EmailVerificationTemplate;
use Middleware\RateLimiter;

class AuthController
{
    public function register()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            return Response::json(400, 'INVALID_DATA');
        }

        $name = trim($data['name'] ?? '');
        $username = trim($data['username'] ?? '');
        $password = (string)($data['password'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');

        if (!Validator::validateName($name)) {
            return Response::json(400, 'INVALID_NAME');
        }

        if (!Validator::validateUsername($username)) {
            return Response::json(400, 'INVALID_USERNAME');
        }

        if (!Validator::validatePassword($password)) {
            return Response::json(400, 'INVALID_PASSWORD');
        }

        if (!Validator::validateEmail($email)) {
            return Response::json(400, 'INVALID_EMAIL');
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $db = (new Database())->getConnection();
        RateLimiter::enforce($db, 'auth-register-ip', 5, 3600);
        $userModel = new User($db);

        if ($userModel->exists($username, $email)) {
            return Response::json(400, 'USER_OR_EMAIL_EXISTS');
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $userId = $userModel->create($name, $username, $hashedPassword, $email, $phone);

        if (!$userId) {
            return Response::json(500, 'REGISTER_ERROR');
        }

        $token = bin2hex(random_bytes(16));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hora

        $stmt = $db->prepare("INSERT INTO email_verifications (user_id, email, token, ip_address, expires_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $email, $token, $ip, $expiresAt]);
        $userModel->logEvent($userId, 'register', 'Usuario registrado exitosamente', $ip);

        $verificationLink = $_ENV['APP_URL'] . "/api/verify-email?token=" . $token;
        $template = EmailVerificationTemplate::generate($name, $verificationLink);
        $mailResult = \Helpers\MailHelper::sendMail($email, $name, $template['subject'], $template['body']);

        if (!$mailResult['success']) {
            return Response::json(500, 'EMAIL_SEND_FAILED');
        }
        $userModel->logEvent($userId, 'verification_email_sent', 'Correo de verificación enviado', $ip);
        return Response::json(201, 'REGISTER_SUCCESS_CHECK_EMAIL');
    }

    public function login()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['identifier']) || !isset($input['password'])) {
            return Response::json(400, 'MISSING_LOGIN_DATA');
        }

        $identifier = trim($input['identifier']);
        $password = $input['password'];

        if (empty($identifier) || empty($password)) {
            return Response::json(400, 'LOGIN_FIELDS_REQUIRED');
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $db = (new Database())->getConnection();

        // Verificar si la IP está bloqueada
        RateLimiter::enforce($db, 'auth-login-ip', 30, 900);
        RateLimiter::enforce($db, 'auth-login-identifier', 10, 900, $ip . '|' . strtolower($identifier));

        $blocked = $db->prepare("SELECT id FROM blocked_ips WHERE ip_address = ? AND (unblock_at IS NULL OR unblock_at > NOW()) LIMIT 1");
        $blocked->execute([$ip]);
        if ($blocked->fetch()) {
            // No sabemos si existe el usuario aún
            (new User($db))->logEvent(null, 'login_failed', 'Intento de inicio de sesión fallido, IP Bloqueado', $ip);
            return Response::json(403, 'IP_BLOCKED');
        }

        $userModel = new User($db);
        $user = $userModel->findByUsernameOrEmail($identifier);

        // Umbrales de .env
        $accountLockAttempts = intval($_ENV['ACCOUNT_LOCK_ATTEMPTS'] ?? 5);
        $ipBlockAttempts = intval($_ENV['IP_BLOCK_ATTEMPTS'] ?? 10);
        $ipBlockDuration = intval($_ENV['IP_BLOCK_DURATION'] ?? 60);

        if (!$user) {
            $userModel->logEvent(null, 'login_failed', 'Intento de inicio de sesión fallido, Usuario incorrecto', $ip);

            // Bloqueo automático de IP por fallos repetidos
            $this->checkAndBlockIp($db, $ip, $ipBlockAttempts, $ipBlockDuration);

            return Response::json(401, 'INVALID_CREDENTIALS');
        }

        if ($user['failed_login_attempts'] >= $accountLockAttempts) {
            $userModel->logEvent($user['id'], 'login_failed', 'Intento de inicio de sesión fallido, Usuario Bloqueado', $ip);
            return Response::json(403, 'ACCOUNT_LOCKED');
        }

        // Verificar email verificado
        $stmt = $db->prepare("SELECT verified FROM email_verifications WHERE user_id = ? AND verified = 1 LIMIT 1");
        $stmt->execute([$user['id']]);
        $verifiedRecord = $stmt->fetch();

        if (!$verifiedRecord) {
            $userModel->logEvent($user['id'], 'login_failed', 'Intento de inicio de sesión fallido, Correo no verificado', $ip);
            return Response::json(403, 'EMAIL_NOT_VERIFIED');
        }

        if (!password_verify($password, $user['password'])) {
            $userModel->incrementFailedAttempts($user['id']);
            $userModel->logEvent($user['id'], 'login_failed', 'Intento de inicio de sesión fallido, Contraseña incorrecta', $ip);

            // Bloqueo automático de IP por fallos repetidos
            $this->checkAndBlockIp($db, $ip, $ipBlockAttempts, $ipBlockDuration);

            return Response::json(401, 'INVALID_CREDENTIALS');
        }

        // Login exitoso
        $issuedAt = time();
        $expirationTime = $issuedAt + intval($_ENV['JWT_EXPIRATION'] ?? ($_ENV['JWT_EXPIRY'] ?? 3600));
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'iss' => $_ENV['JWT_ISSUER'] ?? 'temp-segura',
            'sub' => $user['id'],
            'role' => $user['role'],
            'lang' => $user['preferred_language'] ?? 'es',
            'jti' => bin2hex(random_bytes(16))
        ];

        $jwt = JWT::encode($payload, $this->jwtSecret(), 'HS256');
        $userModel->resetFailedAttempts($user['id']);
        $userModel->logEvent($user['id'], 'login_success', 'Inicio de sesión exitoso', $ip);

        return Response::json(200, 'LOGIN_SUCCESS', [
            'token' => $jwt,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'lang' => $user['preferred_language'] ?? 'es'
            ]
        ]);
    }

    /**
     * Verifica si la IP debe bloquearse automáticamente y la bloquea si corresponde.
     */
    private function checkAndBlockIp($db, $ip, $threshold, $durationMinutes)
    {
        // Contar intentos fallidos en los últimos 30 minutos
        $stmt = $db->prepare("SELECT COUNT(*) FROM event_logs
        WHERE ip_address = ? AND event_type = 'login_failed' AND created_at > (NOW() - INTERVAL 30 MINUTE)");
        $stmt->execute([$ip]);
        $failCount = $stmt->fetchColumn();

        if ($failCount >= $threshold) {
            // Insertar si no existe
            $reason = "IP bloqueada automáticamente por $failCount intentos fallidos";
            $stmt = $db->prepare("INSERT INTO blocked_ips (ip_address, unblock_at, reason) VALUES (?, DATE_ADD(NOW(), INTERVAL ? MINUTE), ?)
    ON DUPLICATE KEY UPDATE unblock_at = VALUES(unblock_at), reason = VALUES(reason)");
            $stmt->execute([$ip, $durationMinutes, $reason]);

            // Registrar evento de bloqueo
            (new User($db))->logEvent(null, 'ip_blocked', "IP bloqueada automáticamente por $failCount intentos fallidos", $ip);
        }
    }

    public function verifyEmail()
    {
        $token = $_GET['token'] ?? null;

        if (!TokenHelper::isHexToken($token, 16)) {
            return Response::json(400, 'TOKEN_NOT_PROVIDED');
        }

        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT * FROM email_verifications WHERE token = ? AND verified = 0 LIMIT 1");
        $stmt->execute([$token]);
        $record = $stmt->fetch(\PDO::FETCH_ASSOC);
        $userModel = new \Models\User($db);

        if (!$record) {
            return Response::json(404, 'INVALID_OR_EXPIRED_TOKEN');
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (isset($record['expires_at']) && strtotime($record['expires_at']) < time()) {
            $userModel->logEvent($record['user_id'], 'email_verified', 'Correo verificado correctamente', $ip);

            return Response::json(400, 'TOKEN_EXPIRED');
        }

        $db->prepare("UPDATE email_verifications SET verified = 1, verified_at = NOW() WHERE id = ?")
            ->execute([$record['id']]);

        $userModel = new \Models\User($db);
        $userModel->verifyEmail($record['user_id']);
        $userModel->logEvent($record['user_id'], 'email_verified', 'Correo verificado correctamente', $ip);

        return Response::json(200, 'EMAIL_VERIFIED_SUCCESSFULLY');
    }

    public function resendEmailVerification()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || empty($data['email'])) {
            return Response::json(400, 'EMAIL_REQUIRED');
        }

        $email = trim($data['email']);
        if (!Validator::validateEmail($email)) {
            return Response::json(400, 'INVALID_EMAIL');
        }

        $db = (new Database())->getConnection();
        RateLimiter::enforce($db, 'auth-resend-verification-ip', 5, 3600);
        RateLimiter::enforce($db, 'auth-resend-verification-email', 3, 3600, strtolower($email));
        $userModel = new User($db);
        $user = $userModel->findByEmail($email);

        if (!$user) {
            return Response::json(200, 'VERIFICATION_EMAIL_RESENT');
        }

        if ((int)$user['is_email_verified'] === 1) {
            return Response::json(200, 'VERIFICATION_EMAIL_RESENT');
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Invalidar tokens anteriores
        $stmt = $db->prepare("UPDATE email_verifications SET verified = -1 WHERE user_id = ?");
        $stmt->execute([$user['id']]);

        // Crear nuevo token
        $token = bin2hex(random_bytes(16));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hora

        $stmt = $db->prepare("INSERT INTO email_verifications (user_id, email, token, ip_address, expires_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user['id'], $email, $token, $ip, $expiresAt]);

        $verificationLink = $_ENV['APP_URL'] . "/api/verify-email?token={$token}";
        $template = \MailTemplates\EmailVerificationTemplate::generate($user['name'], $verificationLink);
        $mailResult = \Helpers\MailHelper::sendMail($email, $user['name'], $template['subject'], $template['body']);



        if (!$mailResult['success']) {
            return Response::json(500, 'EMAIL_SEND_FAILED');
        }
        $userModel->logEvent($user['id'], 'verification_email_resent', 'Correo de verificación reenviado', $ip);
        return Response::json(200, 'VERIFICATION_EMAIL_RESENT');
    }


    public function requestPasswordReset()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || empty($data['email'])) {
            return Response::json(400, 'EMAIL_REQUIRED');
        }

        $email = trim($data['email']);
        if (!Validator::validateEmail($email)) {
            return Response::json(400, 'INVALID_EMAIL');
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $db = (new Database())->getConnection();
        RateLimiter::enforce($db, 'auth-password-reset-ip', 5, 3600);
        RateLimiter::enforce($db, 'auth-password-reset-email', 3, 3600, strtolower($email));

        $blocked = $db->prepare("SELECT id FROM blocked_ips WHERE ip_address = ? AND (unblock_at IS NULL OR unblock_at > NOW()) LIMIT 1");
        $blocked->execute([$ip]);
        if ($blocked->fetch()) {
            return Response::json(403, 'IP_BLOCKED');
        }

        $userModel = new User($db);
        $user = $userModel->findByEmail($email);

        if (!$user) {
            $userModel->logEvent(null, 'password_reset_requested', 'Solicitud de restablecimiento de contraseña, Usuario no encontrado', $ip);
            return Response::json(200, 'RESET_EMAIL_SENT');
        }

        if ((int)$user['is_email_verified'] !== 1) {
            $userModel->logEvent($user['id'], 'password_reset_requested', 'Solicitud de restablecimiento de contraseña, Correo no verificado', $ip);
            return Response::json(200, 'RESET_EMAIL_SENT');
        }
        // Invalida todos los tokens anteriores
        $userModel->invalidatePreviousPasswordResetTokens($user['id']);

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);

        $userModel->createPasswordResetRequest($user['id'], $email, $token, $expiresAt, $ip);

        $resetLink = $_ENV['APP_URL'] . "/api/reset-password?token={$token}";
        $template = \MailTemplates\PasswordResetTemplate::generate($user['name'], $resetLink);
        \Helpers\MailHelper::sendMail($email, $user['name'], $template['subject'], $template['body']);
        $userModel->logEvent($user['id'], 'password_reset_requested', 'Solicitud de restablecimiento de contraseña', $ip);
        return Response::json(200, 'RESET_EMAIL_SENT');
    }

    public function verifyPasswordResetToken()
    {
        $token = $_GET['token'] ?? null;

        if (!TokenHelper::isHexToken($token, 32)) {
            return Response::json(400, 'TOKEN_REQUIRED');
        }

        $db = (new Database())->getConnection();
        $userModel = new User($db);
        $record = $userModel->findValidPasswordResetToken($token);

        if (!$record) {
            return Response::json(404, 'INVALID_OR_EXPIRED_TOKEN');
        }

        return Response::json(200, 'TOKEN_VALID');
    }

    public function resetPassword()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $token = trim($data['token'] ?? '');
        $newPassword = (string)($data['new_password'] ?? '');

        if (!TokenHelper::isHexToken($token, 32) || !$newPassword) {
            return Response::json(400, 'MISSING_TOKEN_OR_PASSWORD');
        }

        if (!Validator::validatePassword($newPassword)) {
            return Response::json(400, 'INVALID_PASSWORD');
        }

        $db = (new Database())->getConnection();
        $userModel = new User($db);

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $result = $userModel->resetPasswordWithToken($token, $hashedPassword);

        if (!$result['success']) {
            if ($result['reason'] === 'invalid_token') {
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

                $resetRecord = $userModel->findValidPasswordResetTokeforLog($token);
                $userId = $resetRecord['user_id'] ?? null;

                if ($userId) {
                    $userModel->logEvent($userId, 'password_reset_fail', 'Intento de restablecimiento de contraseña fallido, Token inválido o expirado', $ip);
                } else {
                    $userModel->logEvent(null, 'password_reset_fail', 'Intento de restablecimiento de contraseña fallido, Token inválido o expirado', $ip);
                }
                return Response::json(400, 'INVALID_OR_EXPIRED_TOKEN');
            }
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

            $resetRecord = $userModel->findValidPasswordResetTokeforLog($token);
            $userId = $resetRecord['user_id'] ?? null;

            if ($userId) {
                $userModel->logEvent($userId, 'password_reset_fail', 'Intento de restablecimiento de contraseña fallido, Error al actualizar la contraseña', $ip);
            } else {
                $userModel->logEvent(null, 'password_reset_fail', 'Intento de restablecimiento de contraseña fallido, Error al actualizar la contraseña', $ip);
            }
            return Response::json(500, 'RESET_PASSWORD_ERROR', ['reason' => $result['reason']]);
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $resetRecord = $userModel->findValidPasswordResetTokeforLog($token);
        $userId = $resetRecord['user_id'] ?? null;

        if ($userId) {
            $db->prepare("UPDATE users SET failed_login_attempts = 0 WHERE id = ?")->execute([$userId]);
            $userModel->logEvent($userId, 'password_reset', 'Contraseña restablecida', $ip);
        } else {
            $userModel->logEvent(null, 'password_reset', 'Contraseña restablecida', $ip);
        }


        return Response::json(200, 'PASSWORD_RESET_SUCCESS');
    }

    private function jwtSecret(): string
    {
        $secret = (string)($_ENV['JWT_SECRET'] ?? '');
        $appEnv = strtolower((string)($_ENV['APP_ENV'] ?? 'development'));
        $placeholderSecrets = [
            'tu_clave_secreta_muy_larga_aqui_123456789',
            'change-me',
            'secret',
        ];

        if (strlen($secret) < 32 || ($appEnv === 'production' && in_array($secret, $placeholderSecrets, true))) {
            return Response::json(500, 'INTERNAL_ERROR');
        }

        return $secret;
    }
}
