<?php

namespace Helpers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailHelper
{
    /**
     * Lee una variable de entorno aceptando los nombres MAIL_* (actuales) y
     * SMTP_* (compatibilidad con codigo previo).
     */
    private static function env(string $primary, string $fallback, $default = null)
    {
        return $_ENV[$primary] ?? $_ENV[$fallback] ?? $default;
    }

    /**
     * Determina si debe usarse el modo de desarrollo (sin SMTP real).
     * Se activa con MAIL_DEV_MODE=true o cuando faltan credenciales validas
     * fuera de produccion (placeholders del .env de ejemplo).
     */
    private static function isDevMode(): bool
    {
        $appEnv = strtolower((string)($_ENV['APP_ENV'] ?? 'development'));

        if (filter_var($_ENV['MAIL_DEV_MODE'] ?? false, FILTER_VALIDATE_BOOL)) {
            if ($appEnv === 'production') {
                // Config peligrosa y silenciosa: en produccion NINGUN correo sale
                // (verificacion de cuenta, reset de contraseña y alertas quedan solo
                // en logs/mail_dev.log). Se registra para que se note.
                Logger::warning('MAIL_DEV_MODE activo en produccion: no se envia ningun correo real', [
                    'app_env' => $appEnv,
                ]);
            }

            return true;
        }

        if ($appEnv === 'production') {
            return false;
        }

        $user = (string)self::env('MAIL_USER', 'SMTP_USER', '');
        $pass = (string)self::env('MAIL_PASS', 'SMTP_PASS', '');
        $placeholders = ['', 'tu_email@gmail.com', 'tu_password', 'tu_password_app_gmail'];

        return in_array($user, $placeholders, true) || in_array($pass, $placeholders, true);
    }

    public static function sendMail($toEmail, $toName, $subject, $bodyHtml, $bodyPlain = '')
    {
        // Modo desarrollo: no se envia por SMTP, se registra el correo en disco
        // para poder tomar el enlace de verificacion/reset durante el testing.
        if (self::isDevMode()) {
            self::logDevMail($toEmail, $toName, $subject, $bodyHtml);
            return ['success' => true, 'dev_mode' => true];
        }

        $mail = new PHPMailer(true);

        try {
            // Config SMTP desde .env (MAIL_* con fallback SMTP_*)
            $mail->isSMTP();
            $mail->Host = (string)self::env('MAIL_HOST', 'SMTP_HOST', 'smtp.gmail.com');
            $mail->SMTPAuth = true;
            $mail->Username = (string)self::env('MAIL_USER', 'SMTP_USER', '');
            $mail->Password = (string)self::env('MAIL_PASS', 'SMTP_PASS', '');
            $mail->Port = (int)self::env('MAIL_PORT', 'SMTP_PORT', 587);
            // El 465 es SMTPS (TLS implicito) y el 587 es STARTTLS. Si no se declara
            // MAIL_SECURE se deduce del puerto: mandar 'tls' al 465 falla la conexion.
            $mail->SMTPSecure = (string)self::env(
                'MAIL_SECURE',
                'SMTP_SECURE',
                $mail->Port === 465 ? 'ssl' : 'tls'
            );
            $mail->addCustomHeader('Content-Language', 'es');
            $mail->CharSet = 'UTF-8';
            $mail->setLanguage('es');

            // Remitente. Si MAIL_FROM_EMAIL quedo con un placeholder, PHPMailer
            // abortaria con "Invalid address"; se cae a MAIL_USER y se avisa.
            $from = (string)self::env('MAIL_FROM_EMAIL', 'SMTP_FROM', '');
            if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
                $fallback = (string)self::env('MAIL_USER', 'SMTP_USER', '');
                Logger::warning('MAIL_FROM_EMAIL no es una direccion valida, se usa MAIL_USER', [
                    'mail_from_email' => $from,
                    'usando' => $fallback,
                ]);
                $from = $fallback;
            }

            $mail->setFrom(
                $from,
                (string)self::env('MAIL_FROM_NAME', 'SMTP_FROM_NAME', 'Temp Segura')
            );

            // Destinatario
            $mail->addAddress($toEmail, $toName);

            // Contenido
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $bodyHtml;
            $mail->AltBody = $bodyPlain ?: strip_tags($bodyHtml);

            $mail->send();
            return ['success' => true];
        } catch (Exception $e) {
            Logger::error('Mail send failed', [
                'to' => $toEmail,
                'subject' => $subject,
                'error' => $mail->ErrorInfo,
            ]);
            return [
                'success' => false,
                'error' => $mail->ErrorInfo
            ];
        }
    }

    private static function logDevMail($toEmail, $toName, $subject, $bodyHtml): void
    {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        // Extraer enlaces (verificacion / reset) para acceso rapido en testing.
        preg_match_all('/https?:\/\/[^\s"\'<>]+/i', (string)$bodyHtml, $links);

        $entry = sprintf(
            "[%s] DEV MAIL\n  To: %s <%s>\n  Subject: %s\n  Links: %s\n%s\n",
            date('Y-m-d H:i:s'),
            $toName,
            $toEmail,
            $subject,
            implode(' | ', $links[0] ?? []),
            str_repeat('-', 60)
        );

        @file_put_contents($logDir . '/mail_dev.log', $entry, FILE_APPEND);
        Logger::info('Dev mail captured (no SMTP)', [
            'to' => $toEmail,
            'subject' => $subject,
            'links' => $links[0] ?? [],
        ]);
    }
}
