<?php

namespace MailTemplates;

class TemperatureAlertTemplate
{
    /**
     * @param string $name      Nombre del destinatario
     * @param string $deviceName Nombre de la heladera
     * @param string $location   Ubicacion de la heladera
     * @param string $type       TEMP_HIGH | TEMP_LOW
     * @param float|null $temperature Temperatura registrada
     * @param float $min Rango minimo configurado
     * @param float $max Rango maximo configurado
     * @param string $recordedAt Fecha/hora de la lectura
     */
    public static function generate($name, $deviceName, $location, $type, $temperature, $min, $max, $recordedAt)
    {
        $isHigh = $type === 'TEMP_HIGH';
        $titulo = $isHigh ? 'Temperatura ALTA detectada' : 'Temperatura BAJA detectada';
        $color = $isHigh ? '#dc2626' : '#2563eb';
        $tempTxt = $temperature === null ? '—' : number_format((float)$temperature, 1) . ' °C';
        $rango = number_format((float)$min, 1) . ' °C a ' . number_format((float)$max, 1) . ' °C';
        $subject = "[Temp Segura] {$titulo}: {$deviceName} ({$tempTxt})";

        $body = "
            <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: auto;'>
                <div style='background-color: {$color}; padding: 20px; text-align: center;'>
                    <h1 style='color: #fff; margin: 0; font-size: 20px;'>⚠ {$titulo}</h1>
                </div>
                <div style='padding: 30px;'>
                    <h2 style='color: {$color};'>Hola {$name},</h2>
                    <p>Se detectó una lectura de temperatura fuera del rango configurado en una de tus heladeras.</p>
                    <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                        <tr><td style='padding: 8px; border-bottom: 1px solid #eee; color: #555;'>Heladera</td><td style='padding: 8px; border-bottom: 1px solid #eee;'><strong>{$deviceName}</strong></td></tr>
                        <tr><td style='padding: 8px; border-bottom: 1px solid #eee; color: #555;'>Ubicación</td><td style='padding: 8px; border-bottom: 1px solid #eee;'>{$location}</td></tr>
                        <tr><td style='padding: 8px; border-bottom: 1px solid #eee; color: #555;'>Temperatura registrada</td><td style='padding: 8px; border-bottom: 1px solid #eee; color: {$color};'><strong>{$tempTxt}</strong></td></tr>
                        <tr><td style='padding: 8px; border-bottom: 1px solid #eee; color: #555;'>Rango permitido</td><td style='padding: 8px; border-bottom: 1px solid #eee;'>{$rango}</td></tr>
                        <tr><td style='padding: 8px; border-bottom: 1px solid #eee; color: #555;'>Fecha y hora</td><td style='padding: 8px; border-bottom: 1px solid #eee;'>{$recordedAt}</td></tr>
                    </table>
                    <p>Ingresá a <strong>Temp Segura</strong> para revisar la heladera, reconocer la alarma y tomar las acciones necesarias.</p>
                    <hr style='border: none; border-top: 1px solid #ddd; margin: 30px 0;'/>
                    <p style='font-size: 12px; color: #999;'>Este es un aviso automático de Temp Segura. © 2026 Temp Segura.</p>
                </div>
            </div>
        ";

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }
}
