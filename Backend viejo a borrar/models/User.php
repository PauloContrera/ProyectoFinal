<?php

require_once "../connection/Connection.php";


class User {
    public static function findByEmailOrUsername($emailOrUsername) {
        $db = new Connection();
        $query = "SELECT * FROM users WHERE email = ? OR username = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("ss", $emailOrUsername, $emailOrUsername);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public static function createUser($username, $password, $email, $phone_number, $role) {
        $db = new Connection();
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $query = "INSERT INTO users (username, password, email, phone_number, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        $stmt->bind_param("sssss", $username, $hashed_password, $email, $phone_number, $role);
        return $stmt->execute();
    }
}

?>
