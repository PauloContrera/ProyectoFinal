<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../utils/TokenHelper.php';
require_once __DIR__ . '/../models/UserModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['name'], $data['email'], $data['phone'], $data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

$name = trim($data['name']);
$email = trim($data['email']);
$phone = trim($data['phone']);
$password = $data['password'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email inválido']);
    exit;
}

try {
    $userModel = new UserModel();

    if ($userModel->emailExiste($email)) {
        http_response_code(409);
        echo json_encode(['error' => 'El email ya está registrado']);
        exit;
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $userId = $userModel->crearUsuario($name, $email, $phone, $hashed);

    if (!$userId) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear usuario']);
        exit;
    }

    if (!$userModel->crearGrupoPorDefecto($userId)) {
        $userModel->eliminarUsuario($userId);
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear grupo por defecto']);
        exit;
    }

    $tokenPayload = [
        'user_id' => $userId,
        'email' => $email,
        'role' => 'cliente',
    ];

    $token = TokenHelper::generate($tokenPayload);

    echo json_encode([
        'message' => 'Usuario registrado exitosamente',
        'token' => $token,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
