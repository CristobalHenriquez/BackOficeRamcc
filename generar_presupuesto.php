<?php
require 'vendor/autoload.php';
require 'includes/correo.php';
require_once 'includes/conexion.php';

$id_municipio = $_GET['id_municipio'] ?? null;

if (!$id_municipio) {
    die("Falta id_municipio.");
}

$stmt = $db->prepare("SELECT m.*, p.nombre AS nombre_provincia, 
            cm.nombre AS nombre_categoria,
            cm.valor AS valor_categoria,
            e.tipo AS tipo_estado
         FROM municipios m
         INNER JOIN provincias p ON m.id_provincia = p.id_provincia
         INNER JOIN categoria_membresias cm ON m.id_cat_membresia = cm.id_categoria_membresia  
         INNER JOIN estados e ON m.id_estado = e.id_estado
        WHERE m.id_municipio = ?");

$stmt->bind_param('i', $id_municipio);
if ($stmt->execute() === false) {
    die("Error al ejecutar la consulta: " . $stmt->error);
}

$result = $stmt->get_result();
if ($result) {
    $municipio = $result->fetch_assoc();
} else {
    die("Error en consulta.");
}

function getPresupuestoHTML($municipio)
{
    $imagePath = '/home/u957245339/domains/administracionramcc.net/public_html/image/RAMCC logo full.png';
    $type = pathinfo($imagePath, PATHINFO_EXTENSION);
    $data = file_get_contents($imagePath);
    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

    ob_start();
?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Presupuesto RAMCC</title>
        <style>
            /* Aquí podrías poner tu CSS externo */
        </style>
    </head>
    <body style="font-family: Arial, sans-serif;">
        <header style="display: flex; align-items: flex-start;">
            <div style="flex-grow: 1;">
                <img src="<?= $base64 ?>" alt="Logo de la empresa" style="max-width: 250px;">
            </div>
            <div style="text-align: right; margin: 0;">
                <h1 style="margin: 0;">Presupuesto membresia</h1>
                <small style="margin: 0;">Fecha: <?= date('d/m/Y') ?></small>
                <p style="margin: 0;">Asociación Civil Red de Acción Climática</p>
                <p style="margin: 0;">Muniagurria 156, Rosario, Santa Fe, Argentina</p>
                <p style="margin: 0;">CUIT: 30-71441987-7</p>
            </div>
        </header>
         <main style="margin: 20px 0;">
            <section style="margin-bottom: 20px;">
                <h2>Detalles del Municipio</h2>
                <p><strong>Nombre:</strong> <?= $municipio['nombre_muni'] ?? 'Desconocido' ?></p>
                <p><strong>Categoría:</strong> <?= $municipio['nombre_categoria'] ?? 'Desconocido' ?></p>
            </section>

            <section>
                <h2>Desglose Financiero</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead style="background-color: #f2f2f2;">
                        <tr>
                            <th style="border: 1px solid black; padding: 8px;">Concepto</th>
                            <th style="border: 1px solid black; padding: 8px;">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="border: 1px solid black; padding: 8px;">Membresía RAMCC</td>
                            <td style="border: 1px solid black; padding: 8px;"><?= $municipio['nombre_categoria'] ?? '0.00' ?></td>
                        </tr>
                        <!-- Añadido IVA y Total solo como ejemplo, ajusta según tu base de datos -->
                        <tr>
                            <td style="border: 1px solid black; padding: 8px;">IVA</td>
                            <td style="border: 1px solid black; padding: 8px;"><?= $municipio['iva'] ?? '0.00' ?></td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 8px;">Total</td>
                            <td style="border: 1px solid black; padding: 8px;"><?= $municipio['valor_categoria'] ?? '0.00' ?></td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </main>

        <footer style="text-align: center; margin-top: 20px;">
            <p>Presupuesto valido hasta el 31 Octubre 2024</p>
            <p>por consultas comunicarse</p>
            <p>FacundoM@administracionRamcc.net</p>
        </footer>
    </body>

    </html>
<?php
    return ob_get_clean();
}

if (isset($_POST['generar'])) {
    $html = getPresupuestoHTML($municipio);
    $dompdf = new \Dompdf\Dompdf();

    $dompdf->set_option("isRemoteEnabled", true);
    $dompdf->set_option("isHtml5ParserEnabled", true);

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $pdf_content = $dompdf->output();

    $current_date = date('d-m-Y');
    $filename = "presupuesto_" . $municipio['nombre_muni'] . "_$current_date.pdf";

    $stmt = $db->prepare("INSERT INTO presupuestos (id_municipio, presupuesto, archivo) VALUES (?, ?, ?)");
    $stmt->bind_param('iss', $id_municipio, $filename, $pdf_content);
    if ($stmt->execute() === false) {
        die("Error al insertar presupuesto en la base de datos: " . $stmt->error);
    }

    // Enviar el PDF por correo
    $subject = "Presupuesto Municipal";
    $body = "Estimado/a " . $municipio['nombre_muni'] . ",
    <br>
    Espero que este mensaje te encuentre bien. Me complace informarte que adjunto a este correo encontraras el presupuesto correspondiente para la membresia anual de " . $municipio['nombre_muni'] . ".
    <br>
    Te invito a revisarlo y, en caso de tener alguna pregunta o requerir alguna aclaracion, no dudes en ponerte en contacto conmigo, agradezco de antemano tu tiempo y consideracion.
    <br>
    Atentamente Facundo Moreyra.
    <br>
    Administracion Ramcc";

    // Enviar el PDF al primer correo electrónico
    if (isset($municipio['correo1']) && !empty($municipio['correo1'])) {
        if (!enviarCorreo($municipio['correo1'], $subject, $body, $pdf_content, $filename)) {
            echo "Error al enviar el correo a correo1.";
        }
    }

    // Enviar el PDF al segundo correo electrónico (nuevo bloque de código)
    if (isset($municipio['correo2']) && !empty($municipio['correo2'])) {
        if (!enviarCorreo($municipio['correo2'], $subject, $body, $pdf_content, $filename)) {
            echo "Error al enviar el correo a correo2.";
        }
    }

    // Enviar el PDF al navegador
    $dompdf->stream($filename);
    exit;
}
?>

<!-- ... [Tu código HTML original aquí] ... -->
<!DOCTYPE html>
<html>

<head>
    <title>Generar Presupuesto</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container my-5">
        <h1 class="text-center mb-4">Vista previa presupuesto</h1>

        <?php echo getPresupuestoHTML($municipio); ?>

        <form method="post" action="">
            <input type="submit" name="generar" value="Generar Presupuesto" class="btn btn-primary">
            <a href="detalle_municipio.php?id_municipio=<?php echo $municipio['id_municipio']; ?>" class="btn btn-secondary">Volver a Detalle Municipio</a>
        </form>

        <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    </div>
</body>

</html>