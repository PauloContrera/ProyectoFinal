<?php

// Desarrollado por Paulo Contrera - https://paulo-contrera.web.app/
    require_once "../connection/Connection.php";

    class Temperatura {

        public static function getAll($fridge_id) {
            $db = new Connection();
            $query = "SELECT `id`, `temperature`, `recorded_at` FROM `temperature_records` WHERE fridge_id=?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $fridge_id);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $response = [];
            if($resultado->num_rows) {
                while($row = $resultado->fetch_assoc()) {
                    $response[] = $row; // Agrega cada fila al array de respuesta
                }
            }
            return $response;
        }
        

        public static function getWhere($id_temperatura) {
            $db = new Connection();
            $query = "SELECT `id`, `temperature`, `recorded_at` FROM `temperature_records` WHERE id=?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $id_temperatura);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $response = [];
            if($resultado->num_rows) {
                while($row = $resultado->fetch_assoc()) {
                    $response[]= $row;
                }
            }
            return $response;
        }

        public static function getLastByFridgeId($fridge_id) {
            $db = new Connection();
            $query = "SELECT id, temperature, recorded_at FROM temperature_records WHERE fridge_id=? ORDER BY `recorded_at` DESC LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $fridge_id);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        }


        
        public static function insert($fridge_id, $temperature, $recorded_at) {
            $db = new Connection();

            /*
            date_default_timezone_set('America/Buenos_Aires');
            $recorded_at = date("Y-m-d H:i:s");
            $alert_generated = false;
            */

            // Asegurar que el timestamp sea válido
            $recorded_at = filter_var($recorded_at, FILTER_SANITIZE_STRING);
            $alert_generated = false;

            // Obtener el rango de temperatura de la heladera
            $query = "SELECT min_temp, max_temp FROM fridges WHERE id=?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $fridge_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $min_temp = $row['min_temp'];
                $max_temp = $row['max_temp'];
                
                // Insertar el registro de temperatura con el timestamp recibido
                $query = "INSERT INTO temperature_records(fridge_id, temperature, recorded_at) VALUES (?,?,?)";
                $stmt = $db->prepare($query);
                $stmt->bind_param("ids", $fridge_id, $temperature, $recorded_at);
                $stmt->execute();
                
                if ($stmt->affected_rows > 0) {
                    // Verificar si la temperatura está fuera del rango
                    if ($temperature < $min_temp || $temperature > $max_temp) {
                        // Insertar alerta
                        $temperature_record_id = $stmt->insert_id;
                        $alert_type = 'Excede el límite';
                        $query = "INSERT INTO alerts(fridge_id, temperature_record_id, alert_type, created_at) VALUES (?, ?, ?, ?)";
                        $stmt = $db->prepare($query);
                        $stmt->bind_param("iiss", $fridge_id, $temperature_record_id, $alert_type, $recorded_at);
                        $stmt->execute();
                        
                        $alert_generated = true;
                    }
                    
                    return [
                        'success' => true,
                        'alert_generated' => $alert_generated
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => 'Error al insertar el registro de temperatura.'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => 'Heladera no encontrada.'
                ];
            }
        }
        
        

/*
        

        public static function insert($temperatura, $alerta) {
            $db = new Connection();
            date_default_timezone_set('America/Buenos_Aires');
            $fecha = date("Y-m-d");
            $hora = date("H:i:s");
            $query = "INSERT INTO temperatura(temperatura, fecha, hora, alerta) VALUES (?,?,?,?)";
            $stmt = $db->prepare($query);
            $stmt->bind_param("ssss",$temperatura, $fecha, $hora, $alerta);
            $stmt->execute();
            return $stmt->affected_rows > 0;
        }

        public static function update($id_cliente, $Zona, $Marca, $Talle, $Sexo) {
            $db = new Connection();
            $query = "UPDATE productos SET Zona=?, Marca=?, Talle=?, Sexo=? WHERE id=?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("ssssi", $Zona, $Marca, $Talle, $Sexo, $id_cliente);
            $stmt->execute();
            return $stmt->affected_rows > 0;
        }

        public static function updateZona($id_cliente, $Zona) {
            $db = new Connection();
            date_default_timezone_set('America/Buenos_Aires');
            $fecha = date("Y-m-d H:i:s");
            $query = "UPDATE productos SET Zona=?, HoraMov=? WHERE id=?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("ssi", $Zona, $fecha, $id_cliente);
            $stmt->execute();
            return $stmt->affected_rows > 0;
        }

        public static function delete($id_cliente) {
            $db = new Connection();
            $query = "DELETE FROM `temperatura` WHERE id=?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $id_cliente);
            $stmt->execute();
            return $stmt->affected_rows > 0;
        }
*/
    }
?>