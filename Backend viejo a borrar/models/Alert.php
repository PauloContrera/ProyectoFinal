<?php

require_once '../connection/Connection.php';

class Alert {

    public static function getLastByFridgeId($fridge_id) {
        $db = new Connection();
        $query = "SELECT id, temperature_record_id, alert_type, created_at FROM `alerts` WHERE fridge_id=? ORDER BY `created_at` DESC LIMIT 1";

        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $fridge_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}
?>
