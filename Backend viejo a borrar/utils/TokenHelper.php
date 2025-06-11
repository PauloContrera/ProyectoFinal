<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;

class TokenHelper {
    private static string $secretKey = 'clave-secreta-segura'; // Cambia por tu clave fuerte y segura
    private static string $algo = 'HS256';

    public static function generate(array $payload): string {
        $now = time();
        $exp = $now + (60 * 60 * 2); // Token válido 2 horas

        $basePayload = array_merge($payload, [
            'iat' => $now,
            'exp' => $exp,
            'iss' => 'tu-app.com',
            'aud' => 'tu-app.com',
        ]);

        return JWT::encode($basePayload, self::$secretKey, self::$algo);
    }

    // Puedes agregar aquí método para validar y decodificar tokens
}
