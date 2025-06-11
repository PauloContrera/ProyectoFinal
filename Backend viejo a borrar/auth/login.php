<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../connection/connection.php';
require_once __DIR__ . '/../utils/TokenHelper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['email'], $data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

$email = trim($data['email']);
$password = $data['password'];

try {
    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT id, password, role FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(401);
        echo json_encode(['error' => 'Credenciales inválidas']);
        exit;
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!password_verify($password, $user['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Credenciales inválidas']);
        exit;
    }

    $tokenPayload = [
        'user_id' => $user['id'],
        'email' => $email,
        'role' => $user['role'],
    ];

    $token = TokenHelper::generate($tokenPayload);

    echo json_encode([
        'message' => 'Login exitoso',
        'token' => $token,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
