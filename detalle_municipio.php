<?php

// Incluir el archivo de conexión a la base de datos
require_once 'includes/conexion.php';

// Verificar si se recibió el ID del municipio a mostrar
if (isset($_GET['id_municipio'])) {
    $id_municipio = $_GET['id_municipio'];

    // Realizar la consulta para obtener los datos del municipio con el ID recibido
    $sql = "SELECT municipios.*, provincias.nombre AS nombre_provincia, categoria_membresias.nombre AS nombre_categoria, estados.tipo AS tipo_estado,
    facturas.factura AS nombre_factura, 
    GROUP_CONCAT(facturas.archivo) AS archivos_factura, GROUP_CONCAT(facturas.id_factura) AS ids_factura,
    GROUP_CONCAT(presupuestos.presupuesto) AS nombres_presupuesto, GROUP_CONCAT(presupuestos.archivo) AS archivos_presupuesto, GROUP_CONCAT(presupuestos.id_presupuesto) AS ids_presupuesto
    FROM municipios
    INNER JOIN provincias ON municipios.id_provincia = provincias.id_provincia
    INNER JOIN categoria_membresias ON municipios.id_cat_membresia = categoria_membresias.id_categoria_membresia
    INNER JOIN estados ON municipios.id_estado = estados.id_estado
    LEFT JOIN facturas ON municipios.id_municipio = facturas.id_municipio
    LEFT JOIN presupuestos ON municipios.id_presupuesto = presupuestos.id_presupuesto
    WHERE municipios.id_municipio = $id_municipio
    GROUP BY municipios.id_municipio";

    $result = mysqli_query($db, $sql);

    // Verificar si la consulta se ejecutó correctamente y si hay resultados
    if ($result && mysqli_num_rows($result) > 0) {
        $municipio = mysqli_fetch_assoc($result);
    } else {
        die("Municipio no encontrado.");
    }

    // Consulta para obtener los archivos de presupuesto asociados al municipio
    $sqlPresupuestos = "SELECT presupuesto, archivo, id_presupuesto FROM presupuestos WHERE id_municipio = $id_municipio";
    $resultPresupuestos = mysqli_query($db, $sqlPresupuestos);
    // Consulta para obtener las facturas asociadas al municipio
    $sqlFacturas = "SELECT factura, archivo, id_factura FROM facturas WHERE id_municipio = $id_municipio";
    $resultFacturas = mysqli_query($db, $sqlFacturas);
} else {
    die("ID de municipio no especificado.");
}
// Cerrar la conexión a la base de datos al final del archivo
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>BackOffice RAMCC Administracion</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <!-- Navbar Brand-->
        <a class="navbar-brand ps-3" href="index.php">RAMCC Administracion</a>
        <!-- Sidebar Toggle-->
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
        <!-- Navbar Search-->
        <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
            <div class="input-group">
                <input class="form-control" type="text" placeholder="Buscar" aria-label="Search for..." aria-describedby="btnNavbarSearch" />
                <button class="btn btn-primary" id="btnNavbarSearch" type="button"><i class="fas fa-search"></i></button>
            </div>
        </form>
        <!-- Navbar-->
        <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user fa-fw"></i></a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item" href="#!">Ajustes</a></li>
                    <li><a class="dropdown-item" href="#!">Registro de actividades</a></li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li><a class="dropdown-item" href="login.php">Salir</a></li>
                </ul>
            </li>
        </ul>
    </nav>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-footer">
                    <div class="small">Usuario:</div>
                    <?php
                    // Verificar si el usuario está logueado
                    if (isset($_SESSION['usuario'])) {
                        $user = $_SESSION['usuario'];
                        echo $user['nombre'] . ' ' . $user['apellido'];
                    } else {
                        echo "No estás logeado";
                    }
                    ?>
                </div>
            </nav>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">BackOffice Ramcc</h1>
                    <!--<ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item active">Red Argentina de municipios frente al cambio climatico</li>
                    </ol>-->
                    <div class="text-center mb-3">
                        <a href="index.php">
                             <img src="/image/RAMCC%20logo%20full.png" alt="Logo de la empresa" class="img-fluid" style="max-width: 300px;">
                        </a>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>
                                    <i class="fas fa-table me-1"></i> Detalle de municipio
                                </span>
                                <!-- Botón para modificar los datos -->
                                <a href="index.php" class="btn btn-secondary">Volver a listado de Municipios</a>
                                <!-- Agrega este botón en la sección del card-header en detalle_municipio.php -->
                                <a href="generar_presupuesto.php?id_municipio=<?php echo $id_municipio; ?>" class="btn btn-success">Generar Presupuesto</a>
                                <a href="modificar_municipio.php?id_municipio=<?php echo $municipio['id_municipio']; ?>" class="btn btn-primary">Modificar</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <!--<tr>
                                    <th>Cod Municipio</th>
                                    <td>?php echo $municipio['id_municipio']; ?></td>
                                </tr>-->
                                <tr>
                                    <th>Nombre del Municipio</th>
                                    <td>
                                        <h5><?php echo $municipio['nombre_muni']; ?></h5>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Provincia</th>
                                    <td><?php echo $municipio['nombre_provincia']; ?></td>
                                </tr>
                                <tr>
                                    <th>Contacto Municipio</th>
                                    <td><?php echo $municipio['contacto_muni']; ?></td>
                                </tr>
                                <tr>
                                    <th>Teléfono Municipio</th>
                                    <td><?php echo $municipio['telefono_muni']; ?></td>
                                </tr>
                                <tr>
                                    <th>Detalle Estado</th>
                                    <td><?php echo $municipio['detalle_estado']; ?></td>
                                </tr>
                                <tr>
                                    <th>Categoría Membresía</th>
                                    <td><?php echo $municipio['nombre_categoria']; ?></td>
                                </tr>
                                <tr>
                                    <th>Estado</th>
                                    <td><?php echo $municipio['tipo_estado']; ?></td>
                                </tr>
                                <tr>
                                    <th>Detalle Factura</th>
                                    <td><?php echo $municipio['detalle_factura']; ?></td>
                                </tr>
                                <tr>
                                    <th>Archivos de Factura</th>
                                    <td>
                                        <?php
                                        if ($resultFacturas && mysqli_num_rows($resultFacturas) > 0) {
                                            while ($filaFactura = mysqli_fetch_assoc($resultFacturas)) {
                                                $facturaURL = "descargar_factura.php?id_factura=" . $filaFactura['id_factura']; // Usar el ID de la factura como parámetro
                                                echo "<a href='$facturaURL' target='_blank'>Factura: " . $filaFactura['factura'] . "</a><br>";
                                            }
                                        } else {
                                            echo "No se encontraron facturas.";
                                        }
                                        ?>
                                    </td>

                                </tr>
                                <tr>
                                    <th>Archivos de Presupuesto</th>
                                    <td>
                                        <?php
                                        if ($resultPresupuestos && mysqli_num_rows($resultPresupuestos) > 0) {
                                            while ($filaPresupuesto = mysqli_fetch_assoc($resultPresupuestos)) {
                                                $presupuestoURL = "mostrar_pdf.php?id_presupuesto=" . $filaPresupuesto['id_presupuesto'];
                                                echo "<a href='$presupuestoURL' target='_blank'>Presupuesto: " . $filaPresupuesto['presupuesto'] . "</a><br>";
                                            }
                                        } else {
                                            echo "No se encontraron presupuestos.";
                                        }
                                        ?>
                                    </td>

                                </tr>
                                <tr>
                                    <th>Correo 1</th>
                                    <td><?php echo $municipio['correo1']; ?></td>
                                </tr>
                                <tr>
                                    <th>Correo 2</th>
                                    <td><?php echo $municipio['correo2']; ?></td>
                                </tr>
                                <tr>
                                    <th>URL Convenio</th>
                                    <td><?php echo $municipio['convenio_url']; ?></td>
                                </tr>
                                <tr>
                                    <th>Fecha de Alta</th>
                                    <td><?php echo $municipio['fecha_alta']; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Desarrollo &copy; Ramcc 2023</div>
                        <div>
                            <a href="#">Privacy Policy</a>
                            &middot;
                            <a href="#">Terms &amp; Conditions</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
    <script src="assets/demo/chart-area-demo.js"></script>
    <script src="assets/demo/chart-bar-demo.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
    <script src="js/datatables-simple-demo.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>