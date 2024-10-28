<?php
session_start();
require_once 'auth.php';
if (!isset($_SESSION['usuario'])) {
    redirigirLogin();
}

require_once 'includes/conexion.php';

// Obtener los datos del usuario
$user = $_SESSION['usuario'];
$user_id = $user['usuario']; // Accede a $user en lugar de $usuario

// Consultar los datos del usuario
$sql_user = "SELECT * FROM usuarios WHERE usuario = ?";
$stmt_user = mysqli_prepare($db, $sql_user);

if (!$stmt_user) {
    die("Error en la consulta SQL: " . mysqli_error($db));
}

mysqli_stmt_bind_param($stmt_user, "i", $user_id);
mysqli_stmt_execute($stmt_user);
$result_user = mysqli_stmt_get_result($stmt_user);
$user_data = mysqli_fetch_assoc($result_user);

mysqli_stmt_close($stmt_user);
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
    <?php
    if (isset($_GET['message'])) {
        echo '<div class="alert">' . htmlspecialchars($_GET['message']) . '</div>';
    }
    ?>
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
                                    <i class="fas fa-table me-1"></i> Base de datos Municipios Ramcc
                                </span>
                                <a href="https://xubio.com/NXV/home" class="btn btn-success">Facturacion</a>
                                <a href="enviar_masivo.php" class="btn btn-warning" onclick="return doubleConfirmMasivo();">Enviar correos masivos</a>
                                <a href="enviar_masivo.php?pendiente=1" class="btn btn-danger" onclick="return doubleConfirmPresupuesto();">Enviar a pendientes</a>
                                <a href="nuevo_municipio.php" class="btn btn-primary">Nuevo Municipio</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple" class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Nombre del Municipio</th>
                                        <th>Provincia</th>
                                        <th>Estado</th>
                                        <th>Categoría</th>
                                        <th>Detalle Estado</th>
                                        <th>Fecha de Alta</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th>Nombre del Municipio</th>
                                        <th>Provincia</th>
                                        <th>Estado</th>
                                        <th>Categoría</th>
                                        <th>Detalle Estado</th>
                                        <th>Fecha de Alta</th>
                                    </tr>
                                </tfoot>
                                <tbody>
                                    <?php
                                    // Realizar la consulta a la base de datos para obtener los datos de municipios, estados y categorias
                                    $sql = "SELECT municipios.id_municipio, municipios.nombre_muni, provincias.nombre, estados.tipo, categoria_membresias.nombre AS categoria, municipios.detalle_estado, municipios.fecha_alta 
                                               FROM municipios
                                               INNER JOIN estados ON municipios.id_estado = estados.id_estado
                                               INNER JOIN provincias ON municipios.id_provincia = provincias.id_provincia
                                               INNER JOIN categoria_membresias ON municipios.id_cat_membresia = categoria_membresias.id_categoria_membresia";

                                    $result = mysqli_query($db, $sql);

                                    // Verificar si la consulta se ejecutó correctamente
                                    if (!$result) {
                                        die("Error en la consulta: " . mysqli_error($db));
                                    }

                                    // Verificar si hay resultados
                                    if (mysqli_num_rows($result) > 0) {
                                        // Iterar sobre los resultados y mostrarlos en la tabla
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            // Obtener la fecha en formato UNIX timestamp
                                            $fecha_alta_timestamp = strtotime($row["fecha_alta"]);
                                            // Formatear la fecha
                                            $fecha_alta_formateada = date("d-m-Y", $fecha_alta_timestamp);
                                            echo "<tr>";
                                            echo "<td><a href='detalle_municipio.php?id_municipio=" . $row['id_municipio'] . "' style='font-size: 1.3em;'>" . $row["nombre_muni"] . "</a></td>";
                                            echo "<td>" . $row["nombre"] . "</td>";
                                            echo "<td>" . $row["tipo"] . "</td>";
                                            echo "<td>" . $row["categoria"] . "</td>";
                                            // Limitar la longitud de detalle_estado a, por ejemplo, 100 caracteres
                                            $detalleEstado = (strlen($row["detalle_estado"]) > 50) ? substr($row["detalle_estado"], 0, 50) . "..." : $row["detalle_estado"];
                                            echo "<td>" . $detalleEstado . "</td>";
                                            echo "<td>" . $fecha_alta_formateada . "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5'>No se encontraron registros.</td></tr>";
                                    }
                                    ?>

                                </tbody>
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
    <script>
        function doubleConfirmMasivo() {
            // Confirmación para envíos masivos
            var firstConfirm = confirm("¿Estás seguro de que deseas enviar los correos masivos?");
            if (firstConfirm) {
                var secondConfirm = confirm("¿Realmente estás seguro? Una vez enviados, los correos no pueden ser revertidos.");
                return secondConfirm;
            }
            return false;
        }

        function doubleConfirmPresupuesto() {
            // Confirmación para enviar presupuestos a pendientes
            var firstConfirm = confirm("¿Estás seguro de que deseas enviar los presupuestos a pendientes?");
            if (firstConfirm) {
                var secondConfirm = confirm("¿Realmente estás seguro? Esta acción no puede ser revertida.");
                return secondConfirm;
            }
            return false;
        }

        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(function() {
                var alertElement = document.querySelector(".alert");
                if (alertElement) {
                    var bsAlert = new bootstrap.Alert(alertElement);
                    bsAlert.close();
                }
            }, 3000); // Desaparecerá después de 3 segundos
        });
    </script>
    <!-- Modal: Proceso Exitoso -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="successModalLabel">Mensaje</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Proceso exitoso.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            <?php if (isset($_GET['message']) && $_GET['message'] == "Proceso exitoso") { ?>
                var successModal = new bootstrap.Modal(document.getElementById('successModal'), {});
                successModal.show();
            <?php } ?>
        });
    </script>
    <script src="js/datatables-simple-demo.js"></script>
</body>

</html>