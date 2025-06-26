<?php
require __DIR__ . '/../vendor/autoload.php';  // Para cargar PHPMailer con Composer
require __DIR__ . '/../helpers/MailHelper.php';  // Ajustá la ruta según dónde pusiste el helper


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();


use Helpers\MailHelper;

$result = MailHelper::sendMail(
    'paulocontrera97@gmail.com',    // Cambiá por tu email de prueba
    'Tu Nombre',
    'Test de envío PHPMailer',
    '<h1>Este es un mail de prueba</h1><p>Enviado usando PHPMailer y SMTP configurado en .env</p>'
);

if ($result['success']) {
    echo "Correo enviado correctamente!";
} else {
    echo "Error enviando correo: " . $result['error'];
}