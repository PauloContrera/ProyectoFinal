<?php

namespace MailTemplates;

class PasswordResetTemplate
{
    public static function generate(string $name, string $resetLink): array
    {
        $subject = "Restablecé tu contraseña | Temp Segura";

        $body = "
        <div style='font-family: Arial, sans-serif; color: #333;'>
            <h2 style='color: #004080;'>Solicitud de recuperación de contraseña</h2>
            <p>Hola {$name},</p>
            <p>Recibimos una solicitud para restablecer tu contraseña en Temp Segura.</p>
            <p>Hacé clic en el siguiente botón para continuar:</p>
            <p style='text-align: center; margin: 30px 0;'>
                <a href='{$resetLink}' style='background-color: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;'>Restablecer contraseña</a>
            </p>
            <p>Este enlace será válido por 1 hora. Si no realizaste esta solicitud, podés ignorar este correo.</p>
            <hr style='border: none; border-top: 1px solid #ddd; margin: 30px 0;'/>
            <p style='font-size: 12px; color: #888;'>Temp Segura © " . date('Y') . ". Todos los derechos reservados.</p>
        </div>
        ";

        return ['subject' => $subject, 'body' => $body];
    }
}
