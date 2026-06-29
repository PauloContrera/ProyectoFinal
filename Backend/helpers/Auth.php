<?php

namespace Helpers;

class Auth
{
    public static function user()
    {
        return $_SERVER['auth_user'] ?? null;
    }
}
