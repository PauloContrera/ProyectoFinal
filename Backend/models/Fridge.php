<?php

require_once '../connection/Connection.php';

class Fridge {
    public static function getById($fridgeId) {
        $db = new Connection();
        $query = "SELECT id, name, location, min_temp, max_temp FROM fridges WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $fridgeId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }


    public static function getLastTemperatureByFridgeId($fridgeId) {
        $db = new Connection();
        $query = "SELECT id, temperature, recorded_at FROM temperature_records WHERE fridge_id = ? ORDER BY recorded_at DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $fridgeId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public static function getLastAlertByFridgeId($fridgeId) {
        $db = new Connection();
        $query = "SELECT id, temperature_record_id, alert_type, created_at FROM alerts WHERE fridge_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $fridgeId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}
