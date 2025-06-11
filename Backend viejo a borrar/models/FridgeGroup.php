public function createDefaultGroup($userId) {
    $name = "Grupo por defecto";
    $description = "Grupo automático creado para el usuario";

    $query = "INSERT INTO fridge_groups (user_id, name, description) VALUES (:user_id, :name, :description)";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);

    return $stmt->execute();
}
