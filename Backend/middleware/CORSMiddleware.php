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

        // En producción, agregar dominios reales desde env
        if ($env = getenv('ALLOWED_ORIGINS')) {
            $allowedOrigins = array_merge($allowedOrigins, array_map('trim', explode(',', $env)));
        }

        return array_values(array_unique(array_filter($allowedOrigins)));
    }

    /**
     * Aplica headers CORS
     */
    public static function handle()
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowedOrigins = self::getAllowedOrigins();

        header('Vary: Origin');

        // Validar origen. Si no coincide, no se emite Access-Control-Allow-Origin.
        if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        }

        // Headers permitidos
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400'); // 24 horas

        // Manejar OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
}
