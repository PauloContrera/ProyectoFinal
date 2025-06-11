<?php

require_once '../connection/Connection.php';

class FridgeGroupMember {
    public static function getByGroupId($groupId) {
        $db = new Connection();
        $query = "SELECT * FROM fridge_group_members WHERE group_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $groupId);
        $stmt->execute();
        $result = $stmt->get_result();
        $members = [];
        while ($row = $result->fetch_assoc()) {
            $members[] = $row;
        }
        return $members;
    }
}
