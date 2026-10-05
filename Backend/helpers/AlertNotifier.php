<?php

namespace Helpers;

use MailTemplates\TemperatureAlertTemplate;
use PDO;

/**
 * Envia el email de notificacion cuando se dispara una alerta de temperatura.
 * El SMS lo gestiona el ESP; aca solo se notifica por correo al dueno de la
 * heladera. Es tolerante a fallos: nunca lanza excepciones hacia el flujo de sync.
 */
class AlertNotifier
{
    /**
     * @return bool true si se envio (o capturo en modo dev) el correo.
     */
    public static function notify(PDO $db, int $deviceId, string $type, ?float $temperature, string $recordedAt): bool
    {
        try {
            $stmt = $db->prepare("
                SELECT d.name AS device_name, d.location, d.min_temp, d.max_temp,
                       u.name AS owner_name, u.email AS owner_email
                FROM devices d
                LEFT JOIN users u ON u.id = d.user_id
                WHERE d.id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $deviceId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return false;
            }

            // Destinatarios: el dueño (cliente) + los responsables con acceso a la
            // heladera. Se deduplica por email para no mandar dos veces al mismo.
            $recipients = [];
            if (!empty($row['owner_email'])) {
                $recipients[strtolower(trim($row['owner_email']))] = $row['owner_name'] ?: 'Usuario';
            }

            $accessStmt = $db->prepare("
                SELECT u.name, u.email
                FROM device_access da
                JOIN users u ON u.id = da.user_id
                WHERE da.device_id = :id AND u.email IS NOT NULL AND u.email <> ''
            ");
            $accessStmt->execute([':id' => $deviceId]);
            foreach ($accessStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $recipients[strtolower(trim($r['email']))] = $r['name'] ?: 'Responsable';
            }

            if (!$recipients) {
                // Heladera sin dueño ni responsables con email: no hay a quien notificar.
                return false;
            }

            $template = TemperatureAlertTemplate::generate(
                $row['owner_name'] ?: 'Usuario',
                $row['device_name'] ?: ('Heladera #' . $deviceId),
                $row['location'] ?: 'Sin ubicación',
                $type,
                $temperature,
                (float)$row['min_temp'],
                (float)$row['max_temp'],
                $recordedAt
            );

            $anyOk = false;
            foreach ($recipients as $email => $name) {
                $result = MailHelper::sendMail($email, $name, $template['subject'], $template['body']);
                $ok = (bool)($result['success'] ?? false);
                $anyOk = $anyOk || $ok;
                Logger::info('Alert email dispatched', [
                    'device_id' => $deviceId,
                    'type' => $type,
                    'to' => $email,
                    'success' => $ok,
                ]);
            }

            return $anyOk;
        } catch (\Throwable $e) {
            Logger::error('Alert email failed', [
                'device_id' => $deviceId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
