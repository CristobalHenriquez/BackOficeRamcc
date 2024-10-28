<?php

require 'vendor/autoload.php';
require 'includes/conexion.php';
require 'includes/correo.php';

$messages = [];

$batchSize = 20;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$pendiente = isset($_GET['pendiente']) ? boolval($_GET['pendiente']) : false;

// Aumentar el tiempo de ejecución
set_time_limit(300);

if ($pendiente) {
    $stmt = $db->prepare("SELECT m.*, p.nombre AS nombre_provincia, 
        cm.nombre AS nombre_categoria,
        cm.valor AS valor_categoria,
        e.tipo AS tipo_estado,
        m.correo1, m.correo2
        FROM municipios m
        INNER JOIN provincias p ON m.id_provincia = p.id_provincia
        INNER JOIN categoria_membresias cm ON m.id_cat_membresia = cm.id_categoria_membresia  
        INNER JOIN estados e ON m.id_estado = e.id_estado
        WHERE e.tipo = 'Pago Pendiente'
        LIMIT ?, ?");
} else {
    $stmt = $db->prepare("SELECT m.*, p.nombre AS nombre_provincia, 
        cm.nombre AS nombre_categoria,
        cm.valor AS valor_categoria,
        e.tipo AS tipo_estado,
        m.correo1, m.correo2
        FROM municipios m
        INNER JOIN provincias p ON m.id_provincia = p.id_provincia
        INNER JOIN categoria_membresias cm ON m.id_cat_membresia = cm.id_categoria_membresia  
        INNER JOIN estados e ON m.id_estado = e.id_estado
        LIMIT ?, ?");
}

if (!$stmt) {
    $messages[] = "Error preparando la consulta: " . $db->error;
    exit;
}

$stmt->bind_param("ii", $offset, $batchSize);

if (!$stmt->execute()) {
    $messages[] = "Error ejecutando la consulta: " . $stmt->error;
    exit;
}

$result = $stmt->get_result();
if (!$result) {
    $messages[] = "Error obteniendo el resultado: " . $stmt->error;
    exit;
}

$municipios = $result->fetch_all(MYSQLI_ASSOC);

foreach ($municipios as $municipio) {
    $html = getPresupuestoHTML($municipio);
    if (empty($html)) {
        $messages[] = "Error generando el presupuesto HTML para " . $municipio['nombre_muni'];
        continue;
    }

    $options = new \Dompdf\Options();
    $options->set("isRemoteEnabled", true);
    $options->set("isHtml5ParserEnabled", true);

    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $pdf_content = $dompdf->output();
    $current_date = date('d-m-Y');
    $filename = "presupuesto_" . $municipio['nombre_muni'] . "_$current_date.pdf";

    $stmt2 = $db->prepare("INSERT INTO presupuestos (id_municipio, presupuesto, archivo) VALUES (?, ?, ?)");
    if (!$stmt2) {
        $messages[] = "Error preparando consulta de inserción: " . $db->error;
        continue;
    }

    $stmt2->bind_param('iss', $municipio['id_municipio'], $filename, $pdf_content);
    if (!$stmt2->execute()) {
        $messages[] = "Error guardando el presupuesto para " . $municipio['nombre_muni'] . ": " . $stmt2->error;
    }
    $stmt2->close();

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

    if (isset($municipio['correo1']) && !empty($municipio['correo1'])) {
        if (!enviarCorreo($municipio['correo1'], $subject, $body, $pdf_content, $filename)) {
            $messages[] = "Error enviando el correo a correo1 para " . $municipio['nombre_muni'];
        }
    }

    if (isset($municipio['correo2']) && !empty($municipio['correo2'])) {
        if (!enviarCorreo($municipio['correo2'], $subject, $body, $pdf_content, $filename)) {
            $messages[] = "Error enviando el correo a correo2 para " . $municipio['nombre_muni'];
        }
    }

    sleep(1);
}

$stmt->close();

if (count($municipios) == $batchSize) {
    $nextOffset = $offset + $batchSize;
    header("Location: enviar_masivo.php?offset=$nextOffset");
    exit();
}

$db->close();

if ($pendiente && empty($messages)) {
    header("Location: index.php?message=Procesando presupuestos con pago pendiente...");
    exit();
}

if (empty($messages)) {
    header("Location: index.php?message=Proceso exitoso");
    exit();
}


// Si llegamos a este punto, es porque hay mensajes de error.
foreach ($messages as $message) {
    echo $message . "<br>";
}

echo "Proceso finalizado.";

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
            /* Aquí podrías poner tu CSS externo, si es que se permite en tu generador de PDF */
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
            <p>Presupuesto valido por 60 dias</p>
            <p>por consultas comunicarse</p>
            <p>FacundoM@administracionRamcc.net</p>
        </footer>
    </body>

    </html>
<?php
    return ob_get_clean();
}
