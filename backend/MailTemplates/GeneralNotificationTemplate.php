 <?php

namespace MailTemplates;

class GeneralNotificationTemplate
{
    public static function generate($name, $messageTitle, $messageBody)
    {
        $subject = $messageTitle;

        $body = "
            <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto;'>
                <div style='background-color: #004080; padding: 20px; text-align: center;'>
                    <img src='https://tempsegura.net/assets/SeguraBlanca-DVDU1wD2.svg' alt='Temp Segura' style='width: 120px;'>
                </div>
                <div style='padding: 30px;'>
                    <h2 style='color: #004080;'>Hola {$name},</h2>
                    <p>{$messageBody}</p>
                    <hr style='border: none; border-top: 1px solid #ddd; margin: 30px 0;'/>
                    <p style='font-size: 14px; color: #555;'>Temp Segura - Monitoreo inteligente y gestión de inventarios.</p>
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
