<?php

namespace Helpers;

class Validator
{
    public static function validateName($name)
    {
        return !empty($name) && strlen($name) >= 2;
    }

    public static function validateUsername($username)
    {
        return preg_match('/^[a-zA-Z0-9_]{3,}$/', $username);
    }

    public static function validatePassword($password)
    {
        return strlen($password) >= 6;
    }

    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}
