<?php
// Incluir el archivo de conexión a la base de datos
require_once 'includes/conexion.php';

// Verificar si se envió el formulario para guardar el nuevo municipio
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los datos del formulario
    $nombre_muni = $_POST["nombre_muni"];
    $id_estado = $_POST["id_estado"];
    $id_cat_membresia = $_POST["id_cat_membresia"];
    $detalle_estado = $_POST["detalle_estado"];
    $fecha_alta = $_POST["fecha_alta"];
    // Agregar más campos aquí
    $id_provincia = $_POST["id_provincia"];
    $contacto_muni = $_POST["contacto_muni"];
    $telefono_muni = $_POST["telefono_muni"];
    $correo1 = $_POST["correo1"];
    $correo2 = $_POST["correo2"];
    $convenio_url = $_POST["convenio_url"];

    // Realizar la inserción en la base de datos
    $sql = "INSERT INTO municipios (nombre_muni, id_estado, id_cat_membresia, detalle_estado, fecha_alta, id_provincia, contacto_muni, telefono_muni, correo1, correo2, convenio_url) 
            VALUES ('$nombre_muni', '$id_estado', '$id_cat_membresia', '$detalle_estado', '$fecha_alta', '$id_provincia', '$contacto_muni', '$telefono_muni', '$correo1', '$correo2', '$convenio_url')";

    if (mysqli_query($db, $sql)) {
        // Redireccionar a la página de lista de municipios (index.php) después de guardar
        header("Location: index.php");
        exit;
    } else {
        // Si ocurre un error en la consulta, mostrar mensaje de error
        die("Error al guardar el municipio: " . mysqli_error($db));
    }
}

// Realizar la consulta para obtener los valores de las tablas relacionadas
$sql_estados = "SELECT id_estado, tipo FROM estados";
$sql_categorias = "SELECT id_categoria_membresia, nombre FROM categoria_membresias";
$sql_provincias = "SELECT id_provincia, nombre FROM provincias"; // Agregar consulta para obtener las provincias

$result_estados = mysqli_query($db, $sql_estados);
$result_categorias = mysqli_query($db, $sql_categorias);
$result_provincias = mysqli_query($db, $sql_provincias); // Ejecutar consulta para obtener las provincias

// Verificar si la consulta se ejecutó correctamente
if (!$result_estados || !$result_categorias || !$result_provincias) {
    die("Error en la consulta: " . mysqli_error($db));
}

// Obtener los datos de las tablas relacionadas
$estados = mysqli_fetch_all($result_estados, MYSQLI_ASSOC);
$categorias = mysqli_fetch_all($result_categorias, MYSQLI_ASSOC);
$provincias = mysqli_fetch_all($result_provincias, MYSQLI_ASSOC); // Obtener datos de las provincias

// Cerrar la conexión a la base de datos
mysqli_close($db);
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
                    <li><a class="dropdown-item" href="#!">Salir</a></li>
                </ul>
            </li>
        </ul>
    </nav>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">

                <div class="sb-sidenav-footer">
                    <div class="small">Usuario:</div>
                    Nombre de usuario logeado
                </div>
            </nav>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Nuevo Municipio</h1>
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
                                <a href="index.php" class="btn btn-secondary">Volver a listado de Municipios</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Formulario para ingresar datos del nuevo municipio -->
                            <form action="nuevo_municipio.php" method="post">
                                <div class="mb-3">
                                    <label for="nombre_muni" class="form-label">Nombre del Municipio</label>
                                    <input type="text" class="form-control" id="nombre_muni" name="nombre_muni" required>
                                </div>
                                <div class="mb-3">
                                    <label for="id_estado" class="form-label">Estado</label>
                                    <select class="form-control" id="id_estado" name="id_estado" required>
                                        <?php foreach ($estados as $estado) : ?>
                                            <option value="<?php echo $estado['id_estado']; ?>"><?php echo $estado['tipo']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="id_cat_membresia" class="form-label">Categoría</label>
                                    <select class="form-control" id="id_cat_membresia" name="id_cat_membresia" required>
                                        <?php foreach ($categorias as $categoria) : ?>
                                            <option value="<?php echo $categoria['id_categoria_membresia']; ?>"><?php echo $categoria['nombre']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="detalle_estado" class="form-label">Detalle Estado</label>
                                    <textarea class="form-control" id="detalle_estado" name="detalle_estado" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="fecha_alta" class="form-label">Fecha de Alta</label>
                                    <input type="date" class="form-control" id="fecha_alta" name="fecha_alta" required>
                                </div>
                                <!-- Ejemplo de nuevos campos agregados -->
                                <div class="mb-3">
                                    <label for="id_provincia" class="form-label">Provincia</label>
                                    <select class="form-control" id="id_provincia" name="id_provincia" required>
                                        <?php foreach ($provincias as $provincia) : ?>
                                            <option value="<?php echo $provincia['id_provincia']; ?>"><?php echo $provincia['nombre']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="contacto_muni" class="form-label">Contacto Municipio</label>
                                    <input type="text" class="form-control" id="contacto_muni" name="contacto_muni" required>
                                </div>
                                <div class="mb-3">
                                    <label for="telefono_muni" class="form-label">Teléfono Municipio</label>
                                    <input type="text" class="form-control" id="telefono_muni" name="telefono_muni" required>
                                </div>
                                <div class="mb-3">
                                    <label for="correo1" class="form-label">Correo 1</label>
                                    <input type="email" class="form-control" id="correo1" name="correo1" required>
                                </div>
                                <div class="mb-3">
                                    <label for="correo2" class="form-label">Correo 2</label>
                                    <input type="email" class="form-control" id="correo2" name="correo2">
                                </div>
                                <div class="mb-3">
                                    <label for="convenio_url" class="form-label">URL Convenio</label>
                                    <input type="text" class="form-control" id="convenio_url" name="convenio_url">
                                </div>
                                <!-- Agrega más campos aquí según sea necesario -->
                                <button type="submit" class="btn btn-primary">Guardar Municipio</button>
                            </form>
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
    <script>
        document.getElementById("submitButton").addEventListener("click", function() {
            this.disabled = true;
        });
    </script>
</body>

</html>