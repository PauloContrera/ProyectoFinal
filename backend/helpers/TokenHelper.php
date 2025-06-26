<?php
namespace Helpers;

class TokenHelper
{
    public static function generateToken($length = 32)
    {
        return bin2hex(random_bytes($length));
    }
}
?>
