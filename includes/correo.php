<?php

$rootDir = $_SERVER['DOCUMENT_ROOT'];
require $rootDir . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Cargamos las variables de entorno
$dotenv = Dotenv::createImmutable($rootDir);
$dotenv->load();

function enviarCorreo($to, $subject, $body, $pdf_content = null, $filename = '')
{
    $mail = new PHPMailer(true);

    try {
        $mail->SMTPDebug = 0;  // Desactiva la depuración
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USERNAME'];
        $mail->Password   = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = 465;

        $mail->setFrom($_ENV['SMTP_USERNAME'], 'Red Argentina de Municipios contra el Cambio Climatico');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        if ($pdf_content) {
            $mail->addStringAttachment($pdf_content, $filename);
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Aquí puedes manejar errores, por ejemplo, registrando el error en un archivo log.
        error_log("Error al enviar correo: " . $e->getMessage());
        return false;
    }
}
