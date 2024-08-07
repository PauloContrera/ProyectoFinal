<?php

// Desarrollado por Paulo Contrera - www.paulo-contrera.com para SygSeguros

    require_once "../connection/Connection.php";

    class Cliente {

        public static function getAll() {
            $db = new Connection();
            $query = "SELECT `id`, `temperatura`, `fecha`, `hora`, `alerta` FROM `temperatura` WHERE 1";
            $resultado = $db->query($query);
            $datos = [];
            if($resultado->num_rows) {
                while($row = $resultado->fetch_assoc()) {
                    $datos[] = $row;
                }
            }
            return $datos;
        }

        public static function getWhere($id_cliente) {
            $db = new Connection();
            $query = "SELECT `id`, `temperatura`, `fecha`, `hora`, `alerta` FROM `temperatura` WHERE id=?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("i", $id_cliente);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $response = [];
            if($resultado->num_rows) {
                while($row = $resultado->fetch_assoc()) {
                    $response = $row;
                }
            }
            return $response;
        }

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

    }
?>