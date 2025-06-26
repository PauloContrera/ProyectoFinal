<?php

namespace Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Helpers\Response;

class AuthMiddleware
{
    public static function verifyToken()
    {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            Response::json(401, 'Token no proporcionado.');
            exit;
        }

        $authHeader = $headers['Authorization'];
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            Response::json(401, 'Formato de token inválido.');
            exit;
        }

        $token = $matches[1];

        try {
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
            // Guardamos los datos en el server para que el controlador pueda accederlos
            $_SERVER['user'] = [
                'id' => $decoded->sub,
                'role' => $decoded->role,
                'lang' => $decoded->lang ?? 'es'
            ];
        } catch (\Firebase\JWT\ExpiredException $e) {
            Response::json(401, 'Token expirado.');
            exit;
        } catch (\Exception $e) {
            Response::json(401, 'Token inválido.');
            exit;
        }
    }
    public static function authorize(array $allowedRoles, callable $callback)
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return Response::json(401, 'Token no proporcionado.');
        }

        $jwt = $matches[1];

        try {
            $decoded = JWT::decode($jwt, new Key($_ENV['JWT_SECRET'], 'HS256'));
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
}
