<?php

require_once '../models/Fridge.php';
require_once '../models/FridgeGroup.php';
require_once '../models/FridgeGroupMember.php';
require_once '../models/Temperatura.php';
require_once '../models/Alert.php';
require_once '../utils/TokenHelper.php';
require_once '../utils/ResponseHelper.php';

class FridgeController {
    
    public static function getFridgesByUser() {
        // Obtener y validar el token de autorización
        $headers = apache_request_headers();
        if (!isset($headers['Authorization'])) {
            echo ResponseHelper::error('Token no proporcionado');
            return;
        }

        $token = str_replace('Bearer ', '', $headers['Authorization']);
        $userData = TokenHelper::validateToken($token);

        if (!$userData) {
            echo ResponseHelper::error('Token inválido');
            return;
        }

        $cliente_id = $userData['id'];

        // Obtener IDs de grupos de heladeras asociados al cliente
        $groupIds = FridgeGroup::getByClientId($cliente_id);
        $combinedFridges = [];

        foreach ($groupIds as $group) {
            $fridgesInGroup = FridgeGroupMember::getByGroupId($group['id']);
            foreach ($fridgesInGroup as $fridge) {
                $fridgeData = Fridge::getById($fridge['fridge_id']);
                $fridgeData['group'] = [
                    'id' => $group['id'],
                    'name' => $group['name'],
                    'description' => $group['description']
                ];

                // Obtener la última temperatura y alerta
                $fridgeData['last_temperature'] = Temperatura::getLastByFridgeId($fridge['fridge_id']);
                $fridgeData['last_alert'] = Alert::getLastByFridgeId($fridge['fridge_id']);
                
                $combinedFridges[] = $fridgeData;
            }
        }



        echo ResponseHelper::success([$combinedFridges]);
    }
}

// Ejecutar el método para obtener las heladeras por usuario
FridgeController::getFridgesByUser();

?>
