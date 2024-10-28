<?php
// Iniciar sesión y comprobar autenticación si es necesario
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/conexion.php';

if (!isset($_GET['id_factura'])) {
    die("ID de factura no especificado.");
}

$id_factura = $_GET['id_factura'];

// Prepara y ejecuta la consulta para obtener el archivo de la base de datos
$stmt = $db->prepare("SELECT archivo FROM facturas WHERE id_factura = ?");
$stmt->bind_param("i", $id_factura);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("Factura no encontrada.");
}

$pdfContentHex = $data['archivo'];
$pdfContent = hex2bin($pdfContentHex); // Convierte la cadena hexadecimal a contenido binario

// Configura los encabezados para decirle al navegador que es un PDF
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="factura.pdf"'); // 'inline' hace que se muestre en el navegador; cambia a 'attachment' para forzar la descarga
header('Content-Length: ' . strlen($pdfContent));

// Envía el contenido del archivo al navegador
echo $pdfContent;
