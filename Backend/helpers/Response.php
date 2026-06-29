<?php

namespace Helpers;

class Response
{
    /**
     * Respuesta JSON estandarizada
     *
     * @param int $statusCode Código HTTP
     * @param string $messageKey Clave del mensaje
     * @param array $data Datos adicionales
     * @return array Respuesta formateada
     */
    public static function json($statusCode, $messageKey, $data = [])
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        header('X-Request-ID: ' . AuditLogger::requestId());

        $response = [
            'success' => $statusCode >= 200 && $statusCode < 300,
            'status' => $statusCode,
            'message' => Message::get($messageKey),
            'data' => $data,
            'request_id' => AuditLogger::requestId(),
            'timestamp' => date('Y-m-d H:i:s')
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Respuesta de éxito
     */
    public static function success($data = [], $message = 'SUCCESS', $statusCode = 200)
    {
        return self::json($statusCode, $message, $data);
    }

    /**
     * Respuesta de error
     */
    public static function error($message = 'ERROR', $statusCode = 400, $data = [])
    {
        return self::json($statusCode, $message, $data);
    }

    /**
     * Respuesta no autorizado
     */
    public static function unauthorized($message = 'UNAUTHORIZED')
    {
        return self::json(401, $message);
    }

    /**
     * Respuesta prohibido
     */
    public static function forbidden($message = 'FORBIDDEN')
    {
        return self::json(403, $message);
    }

    /**
     * Respuesta no encontrado
     */
    public static function notFound($message = 'NOT_FOUND')
    {
        return self::json(404, $message);
    }

    /**
     * Respuesta error interno
     */
    public static function serverError($message = 'SERVER_ERROR')
    {
        return self::json(500, $message);
    }
}
