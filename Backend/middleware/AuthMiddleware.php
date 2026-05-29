<?php

namespace Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Helpers\Response;
use Helpers\Logger;

class AuthMiddleware
{
    public static function verifyToken()
    {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            Logger::security('Token access attempt without Authorization header');
            Response::json(401, 'Token no proporcionado.');
            exit;
        }

        $authHeader = $headers['Authorization'];
        if (!preg_match('/^Bearer\s+(\S+)$/i', trim($authHeader), $matches)) {
            Logger::security('Invalid token format attempt');
            Response::json(401, 'Formato de token inválido.');
            exit;
        }

        $token = $matches[1];

        try {
            $decoded = JWT::decode($token, new Key(self::jwtSecret(), 'HS256'));
            // Guardamos los datos en el server para que el controlador pueda accederlos
            $_SERVER['user'] = [
                'id' => $decoded->sub,
                'role' => $decoded->role,
                'lang' => $decoded->lang ?? 'es'
            ];
            Logger::info('Token validated successfully', ['user_id' => $decoded->sub]);
        } catch (\Firebase\JWT\ExpiredException $e) {
            Logger::security('Expired token attempt', ['error' => $e->getMessage()]);
            Response::json(401, 'Token expirado.');
            exit;
        } catch (\Exception $e) {
            Logger::security('Invalid token attempt', ['error' => $e->getMessage()]);
            Response::json(401, 'Token inválido.');
            exit;
        }
    }

    public static function authorize(array $allowedRoles, callable $callback)
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (!preg_match('/^Bearer\s+(\S+)$/i', trim($authHeader), $matches)) {
            return Response::json(401, 'Token no proporcionado.');
        }

        $jwt = $matches[1];

        try {
            $decoded = JWT::decode($jwt, new Key(self::jwtSecret(), 'HS256'));
            $user = [
                'id' => $decoded->sub,
                'role' => $decoded->role
            ];

            if (!in_array($user['role'], $allowedRoles)) {
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
            Response::json(500, 'INTERNAL_ERROR');
            exit;
        }

        return $secret;
    }
}
