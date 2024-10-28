<?php
require_once 'includes/conexion.php';

if (isset($_GET['id_factura'])) {
    $id_documento = $_GET['id_factura'];
    $query = "SELECT archivo FROM facturas WHERE id_factura = ?";
} elseif (isset($_GET['id_presupuesto'])) {
    $id_documento = $_GET['id_presupuesto'];
    $query = "SELECT archivo FROM presupuestos WHERE id_presupuesto = ?";
} else {
    echo "ID de factura o presupuesto no especificado.";
    mysqli_close($db);
    exit;
}

// Preparamos la consulta
$stmt = mysqli_prepare($db, $query);
mysqli_stmt_bind_param($stmt, "i", $id_documento);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

if ($row) {
    $pdfBlob = $row['archivo'];

    // Configura las cabeceras para mostrar el contenido del BLOB como PDF
    header("Content-type: application/pdf");
    echo $pdfBlob;
} else {
    echo "Documento no encontrado.";
}

mysqli_close($db);
