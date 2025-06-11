<?php

class ResponseHelper {
    public static function success($data) {
        return json_encode([
            'success' => true,
            'data' => $data
        ]);
    }

    public static function error($message) {
        return json_encode([
            'success' => false,
            'error' => $message
        ]);
    }
}

?>
