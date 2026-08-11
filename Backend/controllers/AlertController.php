<?php

namespace Controllers;

use Helpers\AuditLogger;
use Helpers\Response;
use Middleware\AuthMiddleware;
use Models\Alert;
use Models\Device;

class AlertController
{
    private Device $deviceModel;
    private Alert $alertModel;

    public function __construct($db)
    {
        $this->deviceModel = new Device($db);
        $this->alertModel = new Alert($db);
    }

    /**
     * Alarmero: lista las alertas de todas las heladeras accesibles para el usuario.
     * Filtro opcional ?status=active|acknowledged|resolved|open (default open).
     */
    public function getAll()
    {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];

        $allowed = ['active', 'acknowledged', 'resolved', 'open', 'all'];
        $status = $_GET['status'] ?? 'open';
        if (!in_array($status, $allowed, true)) {
            $status = 'open';
        }
        $limit = min(500, max(1, (int)($_GET['limit'] ?? 200)));

        $alerts = $this->alertModel->getForUser((int)$user['id'], $user['role'], $status, $limit);
        return Response::json(200, 'ALERT_LIST', $alerts);
    }

    /**
     * Reconoce una alarma (acknowledged). Requiere acceso de escritura.
     */
    public function acknowledge(int $alertId)
    {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];

        $alert = $this->alertModel->getById($alertId);
        if (!$alert) return Response::json(404, 'ALERT_NOT_FOUND');

        $device = $this->deviceModel->getById((int)$alert['device_id']);
        if (!$device) return Response::json(404, 'FRIDGE_NOT_FOUND');
        if (!$this->canWriteDevice($device, $user)) return Response::json(403, 'ACCESS_DENIED');

        $ok = $this->alertModel->acknowledge($alertId);
        if ($ok) {
            AuditLogger::event('alert_acknowledged', 'Alarma de temperatura reconocida', 'info', [
                'alert_id' => $alertId,
                'device_id' => (int)$alert['device_id'],
                'type' => $alert['type'],
            ], (int)$user['id'], 'device', (string)$alert['device_id'], 'acknowledge_alert');
        }

        return $ok ? Response::json(200, 'ALERT_ACKNOWLEDGED') : Response::json(200, 'NO_CHANGES');
    }

    /**
     * Lista las alertas de una heladera. Requiere acceso de lectura.
     */
    public function getByDevice(int $deviceId)
    {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];

        $device = $this->deviceModel->getById($deviceId);
        if (!$device) return Response::json(404, 'FRIDGE_NOT_FOUND');
        if (!$this->canReadDevice($device, $user)) return Response::json(403, 'ACCESS_DENIED');

        $limit = min(200, max(1, (int)($_GET['limit'] ?? 50)));
        return Response::json(200, 'ALERT_LIST', $this->alertModel->getByDevice($deviceId, $limit));
    }

    /**
     * Marca una alerta como resuelta. Requiere acceso de escritura sobre la heladera.
     */
    public function resolve(int $alertId)
    {
        AuthMiddleware::verifyToken();
        $user = $_SERVER['user'];

        $alert = $this->alertModel->getById($alertId);
        if (!$alert) return Response::json(404, 'ALERT_NOT_FOUND');

        $device = $this->deviceModel->getById((int)$alert['device_id']);
        if (!$device) return Response::json(404, 'FRIDGE_NOT_FOUND');
        if (!$this->canWriteDevice($device, $user)) return Response::json(403, 'ACCESS_DENIED');

        $resolved = $this->alertModel->resolve($alertId);
        if ($resolved) {
            AuditLogger::event('alert_resolved', 'Alerta de temperatura resuelta', 'info', [
                'alert_id' => $alertId,
                'device_id' => (int)$alert['device_id'],
                'type' => $alert['type'],
            ], (int)$user['id'], 'device', (string)$alert['device_id'], 'resolve_alert');
        }

        return $resolved ? Response::json(200, 'ALERT_RESOLVED') : Response::json(200, 'NO_CHANGES');
    }

    private function canReadDevice(array $device, array $user): bool
    {
        if (in_array($user['role'], ['admin', 'superadmin'], true)) return true;
        if ((int)$device['user_id'] === (int)$user['id']) return true;

        return (bool)$this->deviceModel->getAccess((int)$device['id'], (int)$user['id']);
    }

    private function canWriteDevice(array $device, array $user): bool
    {
        if ($user['role'] === 'visitor') return false;
        if (in_array($user['role'], ['admin', 'superadmin'], true)) return true;
        if ((int)$device['user_id'] === (int)$user['id']) return true;

        $access = $this->deviceModel->getAccess((int)$device['id'], (int)$user['id']);
        return $access && (bool)$access['can_modify'];
    }
}
