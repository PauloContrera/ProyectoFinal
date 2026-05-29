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
<?php

namespace Helpers;

class TokenHelper
{
    public static function isHexToken(?string $token, int $bytes): bool
    {
        if ($token === null) {
            return false;
        }

        $token = trim($token);
        $length = $bytes * 2;

        return preg_match('/\A[a-f0-9]{' . $length . '}\z/i', $token) === 1;
    }
}
