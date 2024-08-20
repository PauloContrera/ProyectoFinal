<?php

require_once '../connection/Connection.php';

class FridgeGroup {
    public static function getByClientId($clientId) {
        $db = new Connection();
        $query = "SELECT * FROM fridge_groups WHERE client_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $clientId);
        $stmt->execute();
        $result = $stmt->get_result();
        $groups = [];
        while ($row = $result->fetch_assoc()) {
            $groups[] = $row;
        }
        return $groups;
    }
}
