<?php

namespace MailTemplates;

class EmailVerificationTemplate
{
    public static function generate($name, $verificationLink)
    {
        $subject = 'Bienvenido a Temp Segura - Verifica tu correo electrónico';

        $body = "
            <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto;'>
                <div style='background-color: #004080; padding: 20px; text-align: center;'>
                    <img src='https://tempsegura.net/assets/SeguraBlanca-DVDU1wD2.svg' alt='Temp Segura' style='width: 120px;'>
                </div>
                <div style='padding: 30px;'>
                    <h2 style='color: #004080;'>¡Hola {$name}!</h2>
                    <p>Gracias por registrarte en <strong>Temp Segura</strong>, la plataforma inteligente para el monitoreo de temperatura y gestión de inventarios.</p>
                    <p>Para comenzar, por favor verifica tu correo electrónico haciendo clic en el siguiente botón:</p>
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='{$verificationLink}' style='background-color: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Verificar Correo</a>
                    </p>
                    <p>Si no solicitaste este registro, puedes ignorar este correo.</p>
                    <hr style='border: none; border-top: 1px solid #ddd; margin: 30px 0;'/>
                    <p style='font-size: 14px; color: #555;'>Temp Segura ofrece monitoreo en tiempo real, alertas instantáneas y gestión eficiente para proteger tus activos críticos.</p>
                    <p style='font-size: 12px; color: #999;'>© 2025 Temp Segura. Todos los derechos reservados.</p>
                </div>
            </div>
        ";

        return [
            'subject' => $subject,
            'body' => $body
        ];
    }
}
