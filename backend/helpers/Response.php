<?php

namespace Helpers;

class Response
{
    public static function json($statusCode, $messageKey, $data = [])
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');

        echo json_encode([
            'status' => $statusCode,
            'message' => Message::get($messageKey),
            'data' => $data
        ]);
        exit;
    }
}
