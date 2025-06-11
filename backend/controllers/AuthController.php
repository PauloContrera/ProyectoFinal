<?php

namespace Controllers;

use Helpers\Validator;
use Helpers\Response;
use Models\User;
use Config\Database;
use Firebase\JWT\JWT;

class AuthController
{
    public function register()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            Response::json(400, 'Datos no válidos');
            return;
        }

        $name = trim($data['name'] ?? '');
        $username = trim($data['username'] ?? '');
        $password = trim($data['password'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');

        if (!Validator::validateName($name)) {
            Response::json(400, 'Nombre inválido');
            return;
        }

        if (!Validator::validateUsername($username)) {
            Response::json(400, 'Username inválido');
            return;
        }

        if (!Validator::validatePassword($password)) {
            Response::json(400, 'La contraseña debe tener al menos 6 caracteres');
            return;
        }

        if (!Validator::validateEmail($email)) {
            Response::json(400, 'Email inválido');
            return;
        }

        $db = (new Database())->getConnection();
        $userModel = new User($db);

        if ($userModel->exists($username, $email)) {
            Response::json(400, 'El username o el email ya existen');
            return;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $created = $userModel->create($name, $username, $hashedPassword, $email, $phone);

        if ($created) {
            Response::json(201, 'Usuario registrado correctamente');
        } else {
            Response::json(500, 'Error al registrar el usuario');
        }
    }

    public function login()
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['identifier']) || !isset($input['password'])) {
            return Response::json(400, 'Faltan datos requeridos.');
        }

        $identifier = trim($input['identifier']);
        $password = $input['password'];

        if (empty($identifier) || empty($password)) {
            return Response::json(400, 'Usuario/Email y contraseña son obligatorios.');
        }

        $db = (new Database())->getConnection();
        $userModel = new User($db);
        $user = $userModel->findByUsernameOrEmail($identifier);

        if (!$user) {
            return Response::json(401, 'Usuario o contraseña incorrectos.');
        }

        if ($user['failed_login_attempts'] >= 5) {
            return Response::json(403, 'Demasiados intentos fallidos. Cuenta bloqueada.');
        }

        if (!password_verify($password, $user['password'])) {
            $userModel->incrementFailedAttempts($user['id']);
            return Response::json(401, 'Usuario o contraseña incorrectos.');
        }

        $issuedAt = time();
        $expirationTime = $issuedAt + intval($_ENV['JWT_EXPIRATION']);
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'iss' => $_ENV['JWT_ISSUER'],
            'sub' => $user['id'],
            'role' => $user['role']
        ];

        $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        $userModel->resetFailedAttempts($user['id']);

        return Response::json(200, 'Login exitoso.', [
            'token' => $jwt,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
    }
    
}
