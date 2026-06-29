<?php

namespace Controllers;

use Helpers\Response;
use Helpers\Validator;
use Middleware\AuthMiddleware;
use Models\Device;
use Models\StockItem;

class StockController
{
    private Device $deviceModel;
    private StockItem $stockModel;

    public function __construct($db)
    {
        $this->deviceModel = new Device($db);
        $this->stockModel = new StockItem($db);
    }

    public function getByDevice(int $deviceId)
    {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];

        $device = $this->deviceModel->getById($deviceId);
        if (!$device) return Response::json(404, 'FRIDGE_NOT_FOUND');
        if (!$this->canReadDevice($device, $user)) return Response::json(403, 'ACCESS_DENIED');

        return Response::json(200, 'STOCK_LIST', $this->stockModel->getByDeviceId($deviceId));
    }

    public function create(int $deviceId)
    {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];

        $device = $this->deviceModel->getById($deviceId);
        if (!$device) return Response::json(404, 'FRIDGE_NOT_FOUND');
        if (!$this->canWriteDevice($device, $user)) return Response::json(403, 'ACCESS_DENIED');

        $data = $this->readPayload();
        if (!$data) return Response::json(400, 'MISSING_NAME');

        $stockId = $this->stockModel->create($deviceId, $data, (int)$user['id']);
        return Response::json(201, 'STOCK_CREATED', ['stock_id' => $stockId]);
    }

    public function update(int $stockId)
    {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];

        $stockItem = $this->stockModel->getById($stockId);
        if (!$stockItem) return Response::json(404, 'STOCK_NOT_FOUND');

        $device = $this->deviceModel->getById((int)$stockItem['device_id']);
        if (!$device) return Response::json(404, 'FRIDGE_NOT_FOUND');
        if (!$this->canWriteDevice($device, $user)) return Response::json(403, 'ACCESS_DENIED');

        $data = $this->readPayload();
        if (!$data) return Response::json(400, 'MISSING_NAME');

        $updated = $this->stockModel->update($stockId, $data, (int)$user['id']);
        return $updated ? Response::json(200, 'STOCK_UPDATED') : Response::json(200, 'NO_CHANGES');
    }

    public function delete(int $stockId)
    {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];

        $stockItem = $this->stockModel->getById($stockId);
        if (!$stockItem) return Response::json(404, 'STOCK_NOT_FOUND');

        $device = $this->deviceModel->getById((int)$stockItem['device_id']);
        if (!$device) return Response::json(404, 'FRIDGE_NOT_FOUND');
        if (!$this->canWriteDevice($device, $user)) return Response::json(403, 'ACCESS_DENIED');

        $this->stockModel->delete($stockId, (int)$user['id']);
        return Response::json(200, 'STOCK_DELETED');
    }

    private function readPayload()
    {
        $input = json_decode(file_get_contents("php://input"), true) ?: [];
        $name = trim($input['name'] ?? '');

        if ($name === '') return null;
        if (!Validator::validateName($name)) return null;

        if (isset($input['quantity']) && !Validator::validateNumeric($input['quantity'], 0, 1000000)) {
            return null;
        }

        $expirationDate = empty($input['expiration_date']) ? null : trim((string)$input['expiration_date']);
        if ($expirationDate !== null) {
            $date = \DateTime::createFromFormat('Y-m-d', $expirationDate);
            if (!$date || $date->format('Y-m-d') !== $expirationDate) {
                return null;
            }
        }

        return [
            'name' => $name,
            'quantity' => max(0, (int)($input['quantity'] ?? 0)),
            'expiration_date' => $expirationDate,
        ];
    }

    private function canReadDevice(array $device, array $user): bool
    {
        if (in_array($user['role'], ['admin', 'superadmin'])) return true;
        if ((int)$device['user_id'] === (int)$user['id']) return true;

        return (bool)$this->deviceModel->getAccess((int)$device['id'], (int)$user['id']);
    }

    private function canWriteDevice(array $device, array $user): bool
    {
        if ($user['role'] === 'visitor') return false;
        if (in_array($user['role'], ['admin', 'superadmin'])) return true;
        if ((int)$device['user_id'] === (int)$user['id']) return true;

        $access = $this->deviceModel->getAccess((int)$device['id'], (int)$user['id']);
        return $access && (bool)$access['can_modify'];
    }
}
