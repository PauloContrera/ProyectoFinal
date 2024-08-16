<?php

require_once '../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class TokenHelper {
    private static $secretKey = 'your_secret_key'; // Cambia esto por una clave segura

    public static function generateToken($data) {
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600; // El token expira en 1 hora
        $payload = array(
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'data' => $data
        );

        return JWT::encode($payload, self::$secretKey, 'HS256');
    }

    public static function validateToken($token) {
        try {
            $decoded = JWT::decode($token, new Key(self::$secretKey, 'HS256'));
            return (array) $decoded->data;
        } catch (Exception $e) {
            return null;
        }
    }
}

?>
