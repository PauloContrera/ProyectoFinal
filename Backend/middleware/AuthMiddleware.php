<?php

namespace Middleware;

use Config\Database;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Helpers\AuditLogger;
use Helpers\Response;
use Helpers\Logger;

class AuthMiddleware
{
    public static function verifyToken()
    {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            Logger::security('Token access attempt without Authorization header');
            AuditLogger::event('auth_missing_token', 'Intento de acceso sin Authorization', 'warning', [], null, 'auth', null, 'deny');
            Response::json(401, 'Token no proporcionado.');
            exit;
        }

        $authHeader = $headers['Authorization'];
        if (!preg_match('/^Bearer\s+(\S+)$/i', trim($authHeader), $matches)) {
            Logger::security('Invalid token format attempt');
            AuditLogger::event('auth_invalid_token_format', 'Formato de token invalido', 'warning', [], null, 'auth', null, 'deny');
            Response::json(401, 'Formato de token inválido.');
            exit;
        }

        $token = $matches[1];

        try {
            $decoded = JWT::decode($token, new Key(self::jwtSecret(), 'HS256'));
            $dbUser = self::userFromDatabase((int)$decoded->sub);
            if (!$dbUser) {
                Logger::security('Token references a missing user', ['user_id' => $decoded->sub]);
                AuditLogger::event('auth_user_not_found', 'Token valido para usuario inexistente', 'warning', [
                    'user_id' => $decoded->sub,
                ], null, 'auth', null, 'deny');
                Response::json(401, 'Token invalido.');
                exit;
            }

            // Guardamos los datos en el server para que el controlador pueda accederlos
            $_SERVER['user'] = [
                'id' => (int)$dbUser['id'],
                'role' => $dbUser['role'],
                'lang' => $decoded->lang ?? 'es'
            ];
            Logger::info('Token validated successfully', ['user_id' => $dbUser['id']]);
        } catch (\Firebase\JWT\ExpiredException $e) {
            Logger::security('Expired token attempt', ['error' => $e->getMessage()]);
            AuditLogger::event('auth_expired_token', 'Token expirado', 'warning', [], null, 'auth', null, 'deny');
            Response::json(401, 'Token expirado.');
            exit;
        } catch (\Exception $e) {
            Logger::security('Invalid token attempt', ['error' => $e->getMessage()]);
            AuditLogger::event('auth_invalid_token', 'Token invalido', 'warning', [
                'error' => $e->getMessage(),
            ], null, 'auth', null, 'deny');
            Response::json(401, 'Token inválido.');
            exit;
        }
    }

    public static function authorize(array $allowedRoles, callable $callback)
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (!preg_match('/^Bearer\s+(\S+)$/i', trim($authHeader), $matches)) {
            AuditLogger::event('auth_missing_token', 'Intento de acceso sin Authorization', 'warning', [], null, 'auth', null, 'deny');
            return Response::json(401, 'Token no proporcionado.');
        }

        $jwt = $matches[1];

        try {
            $decoded = JWT::decode($jwt, new Key(self::jwtSecret(), 'HS256'));
            $dbUser = self::userFromDatabase((int)$decoded->sub);
            if (!$dbUser) {
                AuditLogger::event('auth_user_not_found', 'Token valido para usuario inexistente', 'warning', [
                    'user_id' => $decoded->sub,
                ], null, 'auth', null, 'deny');
                return Response::json(401, 'Token invalido.');
            }

            $user = [
                'id' => (int)$dbUser['id'],
                'role' => $dbUser['role']
            ];

            if (!in_array($user['role'], $allowedRoles)) {
                AuditLogger::event('auth_forbidden_role', 'Rol sin permisos para la accion', 'warning', [
                    'role' => $user['role'],
                    'allowed_roles' => $allowedRoles,
                ], (int)$user['id'], 'auth', null, 'deny');
                return Response::json(403, 'Acceso denegado.');
            }

            // Ejecutamos la función permitida
            return call_user_func($callback, $user);
        } catch (\Exception $e) {
            return Response::json(401, 'Token inválido.');
        }
    }
    public static function getCurrentUser()
    {
        if (isset($_SERVER['user'])) {
            return $_SERVER['user'];
        }
        return null;
    }

    private static function jwtSecret(): string
    {
        $secret = (string)($_ENV['JWT_SECRET'] ?? '');
        $appEnv = strtolower((string)($_ENV['APP_ENV'] ?? 'development'));
        $placeholderSecrets = [
            'tu_clave_secreta_muy_larga_aqui_123456789',
            'change-me',
            'secret',
        ];

        if (strlen($secret) < 32 || ($appEnv === 'production' && in_array($secret, $placeholderSecrets, true))) {
            Logger::critical('JWT secret is missing or unsafe');
            AuditLogger::event('jwt_secret_unsafe', 'JWT secret faltante o inseguro', 'critical', [
                'app_env' => $appEnv,
                'secret_length' => strlen($secret),
            ], null, 'auth_config', null, 'validate');
            Response::json(500, 'INTERNAL_ERROR');
            exit;
        }

        return $secret;
    }

    private static function userFromDatabase(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT id, role FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            return $user ?: null;
        } catch (\Throwable $exception) {
            Logger::critical('Unable to validate token user against database', [
                'error' => $exception->getMessage(),
                'user_id' => $userId,
            ]);
            return null;
        }
    }
}
