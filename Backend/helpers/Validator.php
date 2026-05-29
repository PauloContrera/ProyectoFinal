<?php

namespace Helpers;

class Validator
{
    // Constantes de validación
    const MIN_NAME_LENGTH = 2;
    const MAX_NAME_LENGTH = 100;
    const MIN_USERNAME_LENGTH = 3;
    const MAX_USERNAME_LENGTH = 50;
    const MIN_PASSWORD_LENGTH = 8;
    const MAX_PASSWORD_LENGTH = 128;

    /**
     * Valida nombre - mínimo 2 caracteres, máximo 100
     */
    public static function validateName($name)
    {
        $name = trim($name);
        $length = strlen($name);
        return $length >= self::MIN_NAME_LENGTH && $length <= self::MAX_NAME_LENGTH;
    }

    /**
     * Valida usuario - solo alfanuméricos y guiones bajos, 3-50 caracteres
     */
    public static function validateUsername($username)
    {
        $username = trim($username);
        $length = strlen($username);

        // Validar longitud
        if ($length < self::MIN_USERNAME_LENGTH || $length > self::MAX_USERNAME_LENGTH) {
            return false;
        }

        // Validar caracteres: a-z, A-Z, 0-9, _ (sin inicio con número)
        return preg_match('/^[a-zA-Z][a-zA-Z0-9_]{' . (self::MIN_USERNAME_LENGTH - 2) . ',}$/', $username) === 1;
    }

    /**
     * Valida contraseña con requisitos de complejidad
     * Mínimo: 8 caracteres, 1 mayúscula, 1 minúscula, 1 número
     */
    public static function validatePassword($password)
    {
        $length = strlen($password);

        // Validar longitud
        if ($length < self::MIN_PASSWORD_LENGTH || $length > self::MAX_PASSWORD_LENGTH) {
            return false;
        }

        // Validar requisitos de complejidad
        $hasUppercase = preg_match('/[A-Z]/', $password) === 1;
        $hasLowercase = preg_match('/[a-z]/', $password) === 1;
        $hasNumber = preg_match('/[0-9]/', $password) === 1;
        $hasSpecial = preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $password) === 1;

        // Requerir: Mayúscula + Minúscula + Número
        return $hasUppercase && $hasLowercase && $hasNumber;
    }

    /**
     * Valida email
     */
    public static function validateEmail($email)
    {
        $email = trim(strtolower($email));
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && strlen($email) <= 254;
    }

    /**
     * Valida teléfono - formato internacional
     */
    public static function validatePhone($phone)
    {
        $phone = preg_replace('/\s+/', '', $phone);
        // Acepta: +XX-XXX-XXX-XXXX o variaciones
        return preg_match('/^\+?[1-9]\d{1,14}$/', $phone) === 1;
    }

    /**
     * Valida device_code - único, alfanumérico
     */
    public static function validateDeviceCode($code)
    {
        $code = trim(strtoupper($code));
        $length = strlen($code);
        return $length >= 6 && $length <= 32 && preg_match('/^[A-Z0-9\-]+$/', $code) === 1;
    }

    /**
     * Valida temperatura - número entre -50 y 80
     */
    public static function validateTemperature($temp)
    {
        $temp = floatval($temp);
        return $temp >= -50 && $temp <= 80;
    }

    /**
     * Obtiene errores de validación de contraseña detallados
     */
    public static function getPasswordErrors($password)
    {
        $errors = [];
        $length = strlen($password);

        if ($length < self::MIN_PASSWORD_LENGTH) {
            $errors[] = 'Mínimo ' . self::MIN_PASSWORD_LENGTH . ' caracteres';
        }
        if ($length > self::MAX_PASSWORD_LENGTH) {
            $errors[] = 'Máximo ' . self::MAX_PASSWORD_LENGTH . ' caracteres';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Debe contener al menos una mayúscula';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Debe contener al menos una minúscula';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Debe contener al menos un número';
        }

        return $errors;
    }

    /**
     * Sanitiza y valida input general
     */
    public static function sanitizeString($input, $maxLength = 255)
    {
        $input = trim($input);
        if (strlen($input) > $maxLength) {
            return false;
        }
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Valida entrada numérica
     */
    public static function validateNumeric($value, $min = null, $max = null)
    {
        if (!is_numeric($value)) {
            return false;
        }
        $value = floatval($value);
        if ($min !== null && $value < $min) {
            return false;
        }
        if ($max !== null && $value > $max) {
            return false;
        }
        return true;
    }
}
