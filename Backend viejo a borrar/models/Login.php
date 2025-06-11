<?php

require_once '../models/User.php';
require_once '../utils/ResponseHelper.php';
require_once '../utils/TokenHelper.php';

class Login {
    public static function authenticate($emailOrUsername, $password) {
        $user = User::findByEmailOrUsername($emailOrUsername);

        if ($user && password_verify($password, $user['password'])) {
            $token = TokenHelper::generateToken([
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ]);

            return ResponseHelper::success(['token' => $token]);
        } else {
            return ResponseHelper::error('Credenciales invalidas');
        }
    }
}

?>
