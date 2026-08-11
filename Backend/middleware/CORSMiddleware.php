<?php

namespace Middleware;

class CORSMiddleware
{
    /**
     * Dominios permitidos para CORS
     */
    private static function getAllowedOrigins()
    {
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:5173',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:5173'
        ];

        // En producción, agregar dominios reales desde env.
        // Se usa $_ENV porque phpdotenv (createImmutable) no puebla getenv().
        $env = $_ENV['ALLOWED_ORIGINS'] ?? getenv('ALLOWED_ORIGINS');
        if ($env) {
            $allowedOrigins = array_merge($allowedOrigins, array_map('trim', explode(',', $env)));
        }

        return array_values(array_unique(array_filter($allowedOrigins)));
    }

    /**
     * Aplica headers CORS
     */
    /**
     * Fuera de produccion se acepta cualquier puerto de localhost.
     *
     * Vite arranca en 5173, pero si ese puerto esta ocupado se mueve solo a otro y
     * la API rechazaba el origen sin decir por que. En produccion sigue valiendo
     * unicamente la lista de ALLOWED_ORIGINS.
     */
    private static function isLocalDevOrigin(string $origin): bool
    {
        $appEnv = strtolower((string)($_ENV['APP_ENV'] ?? 'development'));
        if ($appEnv === 'production') {
            return false;
        }

        return preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#', $origin) === 1;
    }

    public static function handle()
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowedOrigins = self::getAllowedOrigins();

        header('Vary: Origin');

        // Validar origen. Si no coincide, no se emite Access-Control-Allow-Origin.
        if ($origin !== '' && (in_array($origin, $allowedOrigins, true) || self::isLocalDevOrigin($origin))) {
            header('Access-Control-Allow-Origin: ' . $origin);
        }

        // Headers permitidos.
        // No se emite Access-Control-Allow-Credentials: la sesion viaja en el header
        // Authorization, no en cookies, asi que habilitar credenciales solo ampliaria
        // la superficie sin que nada lo use.
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Expose-Headers: X-Request-ID, Retry-After');
        header('Access-Control-Max-Age: 86400'); // 24 horas

        // Manejar OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
}
