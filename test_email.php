<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use Dotenv\Dotenv;

$rootDir = __DIR__;
$dotenv = Dotenv::createImmutable($rootDir);
$dotenv->load();

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['SMTP_USERNAME'];
    $mail->Password   = $_ENV['SMTP_PASSWORD'];
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;

    $mail->setFrom($_ENV['SMTP_USERNAME'], 'Test Name');
    $mail->addAddress('cristobalhb@live.com');  // Cambia a tu dirección de correo electrónico para probar

    $mail->isHTML(true);
    $mail->Subject = 'Test Subject';
    $mail->Body    = 'This is a test email.';

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>
