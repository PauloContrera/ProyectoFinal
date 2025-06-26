<?php

namespace Helpers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailHelper
{
    public static function sendMail($toEmail, $toName, $subject, $bodyHtml, $bodyPlain = '')
    {
        $mail = new PHPMailer(true);

        try {
            // Config SMTP desde .env
            $mail->isSMTP();
            $mail->Host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['SMTP_USER'] ?? '';
            $mail->Password = $_ENV['SMTP_PASS'] ?? '';
            $mail->SMTPSecure = $_ENV['SMTP_SECURE'] ?? 'tls'; // tls o ssl
            $mail->Port = $_ENV['SMTP_PORT'] ?? 587;
            $mail->addCustomHeader('Content-Language', 'es');
            $mail->CharSet = 'UTF-8';
            $mail->setLanguage('es');


            // Remitente
            $mail->setFrom(
                $_ENV['SMTP_FROM'] ?? $_ENV['SMTP_USER'],
                $_ENV['SMTP_FROM_NAME'] ?? 'NoReply'
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
            return [
                'success' => false,
                'error' => $mail->ErrorInfo
            ];
        }
    }
}
