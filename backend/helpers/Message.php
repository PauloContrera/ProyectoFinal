<?php

namespace Helpers;

class Message
{
    private static $messages = [];

    private static function loadMessages($lang = 'es')
    {
        if (empty(self::$messages)) {
            $file = __DIR__ . "/../config/lang/{$lang}.php";
            if (file_exists($file)) {
                self::$messages = require $file;
            } else {
                self::$messages = require __DIR__ . '/../config/lang/es.php';
            }
        }
    }

public static function get($key)
{
    $lang = $_SERVER['user']['lang'] ?? 'es';
    self::loadMessages($lang);
    return self::$messages[$key] ?? $key;
}

}
