<?php
namespace Models;

use PDO;

class Device
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Crear una heladera
    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO devices 
            (name, location, user_id, group_id, min_temp, max_temp, firmware_version) 
            VALUES 
            (:name, :location, :user_id, :group_id, :min_temp, :max_temp, :firmware_version)
        ");
        $stmt->execute([
            ':name' => $data['name'],
            ':location' => $data['location'],
            ':user_id' => $data['user_id'],
            ':group_id' => $data['group_id'],
            ':min_temp' => $data['min_temp'],
            ':max_temp' => $data['max_temp'],
            ':firmware_version' => $data['firmware_version'] ?? null
        ]);
        return $this->db->lastInsertId();
    }

    // Obtener una heladera por ID
    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM devices WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obtener todas las heladeras del usuario
    public function getAllByUser($userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM devices WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener heladeras accesibles por device_access
    public function getAccessibleDevices($userId)
    {
        $stmt = $this->db->prepare("
            SELECT d.* 
            FROM devices d
            INNER JOIN device_access da ON d.id = da.device_id
            WHERE da.user_id = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Actualizar una heladera
    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE devices SET
            name = :name,
            location = :location,
            group_id = :group_id,
            min_temp = :min_temp,
            max_temp = :max_temp,
            firmware_version = :firmware_version
            WHERE id = :id
        ");
        return $stmt->execute([
            ':name' => $data['name'],
            ':location' => $data['location'],
            ':group_id' => $data['group_id'],
            ':min_temp' => $data['min_temp'],
            ':max_temp' => $data['max_temp'],
            ':firmware_version' => $data['firmware_version'],
            ':id' => $id
        ]);
    }

    // Eliminar una heladera
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM devices WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // Registrar un cambio en el log
    public function logChange($deviceId, $field, $old, $new, $changedBy)
    {
        $stmt = $this->db->prepare("
            INSERT INTO device_change_log 
            (device_id, field_changed, old_value, new_value, changed_by)
            VALUES
            (:device_id, :field_changed, :old_value, :new_value, :changed_by)
        ");
        return $stmt->execute([
            ':device_id' => $deviceId,
            ':field_changed' => $field,
            ':old_value' => $old,
            ':new_value' => $new,
            ':changed_by' => $changedBy
        ]);
    }

    // Agregar acceso a usuario
    public function grantAccess($deviceId, $userId, $canModify)
    {
        $stmt = $this->db->prepare("
            INSERT INTO device_access (device_id, user_id, can_modify)
            VALUES (:device_id, :user_id, :can_modify)
            ON DUPLICATE KEY UPDATE can_modify = :can_modify
        ");
        return $stmt->execute([
            ':device_id' => $deviceId,
            ':user_id' => $userId,
            ':can_modify' => $canModify ? 1 : 0
        ]);
    }

    // Revocar acceso
    public function revokeAccess($deviceId, $userId)
    {
        $stmt = $this->db->prepare("
            DELETE FROM device_access
            WHERE device_id = :device_id AND user_id = :user_id
        ");
        return $stmt->execute([
            ':device_id' => $deviceId,
            ':user_id' => $userId
        ]);
    }

    // Consultar permisos de un usuario sobre una heladera
    public function getAccess($deviceId, $userId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM device_access
            WHERE device_id = :device_id AND user_id = :user_id
        ");
        $stmt->execute([
            ':device_id' => $deviceId,
            ':user_id' => $userId
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
