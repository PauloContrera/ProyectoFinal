<?php
use Controllers\AuthController;
use Controllers\UserController;
use Middleware\AuthMiddleware;
use Config\Database;

global $relativeUri, $requestMethod;

// Autenticación
if ($relativeUri === '/api/register' && $requestMethod === 'POST') {
    (new AuthController())->register();
    exit;
}

if ($relativeUri === '/api/login' && $requestMethod === 'POST') {
    (new AuthController())->login();
    exit;
}

if ($relativeUri === '/api/verify-email' && $requestMethod === 'GET') {
    (new AuthController())->verifyEmail();
    exit;
}

if ($relativeUri === '/api/resend-email-verification' && $requestMethod === 'POST') {
    (new AuthController())->resendEmailVerification();
    exit;
}

if ($relativeUri === '/api/request-password-reset' && $requestMethod === 'POST') {
    (new AuthController())->requestPasswordReset();
    exit;
}

if ($relativeUri === '/api/reset-password/verify' && $requestMethod === 'GET') {
    (new AuthController())->verifyPasswordResetToken();
    exit;
}

if ($relativeUri === '/api/reset-password' && $requestMethod === 'POST') {
    (new AuthController())->resetPassword();
    exit;
}

// Usuarios protegidos por token
if (preg_match('#^/api/users/(\d+)$#', $relativeUri, $matches)) {
    $userId = $matches[1];
    AuthMiddleware::verifyToken();
    $controller = new UserController((new Database())->getConnection());

    if ($requestMethod === 'GET') {
        $controller->getById($userId);
        exit;
    }
    if ($requestMethod === 'PUT') {
        $controller->update($userId);
        exit;
    }
    if ($requestMethod === 'DELETE') {
        $controller->delete($userId);
        exit;
    }
}

if (preg_match('#^/api/users/(\d+)/change-password$#', $relativeUri, $matches) && $requestMethod === 'PUT') {
    $userId = $matches[1];
    AuthMiddleware::verifyToken();
    (new UserController((new Database())->getConnection()))->changePassword($userId);
    exit;
}

if (preg_match('#^/api/users/(\d+)/change-username$#', $relativeUri, $matches) && $requestMethod === 'PUT') {
    $userId = $matches[1];
    AuthMiddleware::verifyToken();
    (new UserController((new Database())->getConnection()))->changeUsername($userId);
    exit;
}

// Obtener todos los usuarios
if ($relativeUri === '/api/users' && $requestMethod === 'GET') {
    AuthMiddleware::verifyToken();
    (new UserController((new Database())->getConnection()))->getAll();
    exit;
}

// Endpoint de prueba
if ($relativeUri === '/api/test' && $requestMethod === 'GET') {
    AuthMiddleware::verifyToken();
    (new UserController((new Database())->getConnection()))->testLog();
    exit;
}
