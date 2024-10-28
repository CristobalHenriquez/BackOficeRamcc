<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/conexion.php';
$id_municipio = isset($_GET['id_municipio']) ? intval($_GET['id_municipio']) : null;


// Añadir la lógica de eliminación aquí
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete') {

    // Para eliminar facturas
    if (isset($_POST['id_factura'])) {
        $id_factura = $_POST['id_factura'];
        $query = "DELETE FROM facturas WHERE id_factura = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $id_factura);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar la factura.']);
        }
        exit();

        // Para eliminar presupuestos
    } elseif (isset($_POST['id_presupuesto'])) {
        $id_presupuesto = $_POST['id_presupuesto'];
        $query = "DELETE FROM presupuestos WHERE id_presupuesto = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $id_presupuesto);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el presupuesto.']);
        }
        exit();
    }
}



$municipio = [];

if (isset($_GET['id_municipio'])) {
    $id_municipio = $_GET['id_municipio'];
    $stmt = $db->prepare("SELECT municipios.*, estados.tipo AS estado, categoria_membresias.nombre AS categoria, categoria_membresias.id_categoria_membresia AS id_categoria_membresia 
               FROM municipios 
               JOIN estados ON municipios.id_estado = estados.id_estado 
               JOIN categoria_membresias ON municipios.id_cat_membresia = categoria_membresias.id_categoria_membresia 
               WHERE municipios.id_municipio = ?");

    if ($stmt === false) {
        die("Error en la consulta: " . $db->error);
    }

    $stmt->bind_param("i", $id_municipio);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $municipio = $result->fetch_assoc();
        } else {
            $error = "Municipio no encontrado.";
        }
    } else {
        $error = "Error al ejecutar la consulta.";
    }
} else {
    $error = "ID de municipio no especificado.";
}
$resultEstados = mysqli_query($db, "SELECT * FROM estados");
$resultCategorias = mysqli_query($db, "SELECT * FROM categoria_membresias");
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($municipio['id_municipio'])) {
    $contacto_muni = $_POST['contacto_muni'];
    $telefono_muni = $_POST['telefono_muni'];
    $convenio_url = $_POST['convenio_url'];
    $detalle_estado = $_POST['detalle_estado'];
    $categoria_membresia = $_POST['categoria_membresia'];
    $estado = $_POST['estado'];
    $correo1 = $_POST['correo1'];
    $correo2 = $_POST['correo2'];
    $detalle_factura = $_POST['detalle_factura'];
    $nueva_factura = $_POST['nueva_factura'];

    $errors = [];

    if (filter_var($convenio_url, FILTER_VALIDATE_URL) === false) {
        $errors[] = "URL no válida.";
    }

    // ...otros errores...

    if (!empty($errors)) {
        // ...maneja los errores...
    }
    // Inicio de la nueva comprobación de archivos
    if (isset($_FILES['archivo_factura'])) {
        if ($_FILES['archivo_factura']['error'] == UPLOAD_ERR_OK) {
            $archivo_factura = $_FILES['archivo_factura'];

            // Leer el contenido del archivo
            $contenidoFactura = file_get_contents($archivo_factura['tmp_name']);
            if ($contenidoFactura !== false) {
                // Aquí comienza el nuevo código que agregas
                $contenidoFacturaHex = bin2hex($contenidoFactura); // Convierte el contenido a cadena hexadecimal

                $sqlInsertFactura = "INSERT INTO facturas (id_municipio, factura, archivo) VALUES (?, ?, ?)";
                $stmtFactura = $db->prepare($sqlInsertFactura);

                if ($stmtFactura === false) {
                    die("Error al preparar la consulta de inserción de factura: " . $db->error);
                }

                $stmtFactura->bind_param("iss", $id_municipio, $nueva_factura, $contenidoFacturaHex);

                if (!$stmtFactura->execute()) {
                    $error = "Error al insertar nueva factura.";
                }
                // Fin del nuevo código que agregas
            } else {
                $error = "Error al leer el contenido del archivo.";
            }
        } else {
            $error = "Error al cargar el archivo: " . $_FILES['archivo_factura']['error'];
        }
    }
    // Asegúrate de actualizar con los IDs correspondientes si es necesario
    $sqlUpdate = "UPDATE municipios SET contacto_muni = ?, telefono_muni = ?, detalle_estado = ?, id_cat_membresia = ?, id_estado = ?, correo1 = ?, correo2 = ?, convenio_url = ? WHERE id_municipio = ?";
    // Usamos una consulta preparada para actualizar el municipio
    $stmtUpdate = $db->prepare($sqlUpdate);
    if ($stmtUpdate === false) {
        die("Error al preparar la consulta de actualización: " . $db->error);
    }
    $stmtUpdate->bind_param("sssiisssi", $contacto_muni, $telefono_muni, $detalle_estado, $categoria_membresia, $estado, $correo1, $correo2, $convenio_url, $id_municipio);

    if ($stmtUpdate->execute()) {
        header("Location: detalle_municipio.php?id_municipio=$id_municipio");
        exit();
    } else {
        $error = "Error al actualizar el municipio.";
    }
}
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
    <!-- Modal de confirmación de eliminación -->
    <!-- Modal de Confirmación -->
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Eliminar Municipio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (isset($municipio['nombre'])) : ?>
                        <span id="deleteMessage"><?php echo $municipio['nombre']; ?> será eliminado. ¿Estás seguro de que deseas continuar?</span>
                        <span id="deleteSuccessMessageModal" style="display: none;">El municipio ha sido eliminado correctamente.</span>
                    <?php else : ?>
                        Municipio será eliminado. ¿Estás seguro de que deseas continuar?
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Confirmar Eliminación</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mensaje de eliminación exitosa fuera del modal -->
    <div id="deleteSuccessAlert" class="alert alert-success alert-dismissible fade show fixed-top m-3" role="alert" style="display: none; font-size: 16px; padding: 10px;">
        El municipio ha sido eliminado correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <!-- Aquí agregamos el modal de confirmación -->
    <div class="modal fade" id="deleteConfirmModalFactura" tabindex="-1" aria-labelledby="deleteFacturaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteFacturaModalLabel">Eliminar Factura</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ¿Seguro que deseas eliminar esta factura?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteFactura">Confirmar Eliminación</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal eliminar presupuesto -->
    <div class="modal fade" id="deleteConfirmModalPresupuesto" tabindex="-1" aria-labelledby="deletePresupuestoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deletePresupuestoModalLabel">Eliminar Presupuesto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ¿Seguro que deseas eliminar este presupuesto?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDeletePresupuesto">Confirmar Eliminación</button>
                </div>
            </div>
        </div>
    </div>
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
                <?php if (isset($error)) : ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

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
                                <!-- Este es tu botón actualizado -->
                                <button id="deleteButton" class="btn btn-danger">Eliminar Municipio</button>
                                <a href="modificar_municipio.php?id_municipio=<?php echo $municipio['id_municipio']; ?>" class="btn btn-primary">Modificar</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <h4>Modificar Municipio</h4>
                            <form method="post" action="modificar_municipio.php?id_municipio=<?php echo $id_municipio; ?>" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label>Contacto Municipio</label>
                                    <input type="text" name="contacto_muni" class="form-control" value="<?php echo $municipio['contacto_muni']; ?>">
                                </div>
                                <div class="mb-3">
                                    <label>Teléfono Municipio</label>
                                    <input type="text" name="telefono_muni" class="form-control" value="<?php echo $municipio['telefono_muni']; ?>">
                                </div>
                                <div class="mb-3">
                                    <label>Detalle Estado</label>
                                    <input type="text" name="detalle_estado" class="form-control" value="<?php echo $municipio['detalle_estado']; ?>">
                                </div>
                                <!-- Estado -->
                                <div class="mb-3">
                                    <label>Estado</label>
                                    <select name="estado" class="form-control">
                                        <?php while ($estado = mysqli_fetch_assoc($resultEstados)) : ?>
                                            <option value="<?php echo $estado['id_estado']; ?>" <?php if ($estado['id_estado'] == $municipio['id_estado']) echo "selected"; ?>>
                                                <?php echo $estado['tipo']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <!-- Categoría Membresía -->
                                <div class="mb-3">
                                    <label>Categoría Membresía</label>
                                    <select name="categoria_membresia" class="form-control">
                                        <?php while ($categoria = mysqli_fetch_assoc($resultCategorias)) : ?>
                                            <option value="<?php echo $categoria['id_categoria_membresia']; ?>" <?php if (isset($municipio['id_categoria_membresia']) && $categoria['id_categoria_membresia'] == $municipio['id_categoria_membresia']) echo "selected"; ?>>
                                                <?php echo $categoria['nombre']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <!-- Detalle Factura -->
                                <div class="mb-3">
                                    <label>Detalle de Factura</label>
                                    <input type="text" name="detalle_factura" class="form-control" value="<?php echo $municipio['detalle_factura']; ?>">
                                </div>
                                <!-- Listado de facturas existentes -->
                                <div class="mb-3">
                                    <label>Facturas Asociadas:</label>
                                    <ul>
                                        <?php
                                        $queryFacturas = "SELECT id_factura, factura, archivo FROM facturas WHERE id_municipio = $id_municipio";
                                        $resultFacturas = mysqli_query($db, $queryFacturas);
                                        while ($factura = mysqli_fetch_assoc($resultFacturas)) : ?>
                                            <li>
                                                <?php echo $factura['factura']; ?>
                                                <a href="descargar_factura.php?id_factura=<?php echo $factura['id_factura']; ?>">Ver Factura</a>
                                                <!-- Aquí agregamos el botón de eliminar -->
                                                <button class="delete-factura" data-id="<?php echo $factura['id_factura']; ?>">Eliminar</button>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                </div>
                                <!-- Agregar nuevas facturas -->
                                <!-- Listado de presupuestos existentes -->
                                <div class="mb-3">
                                    <label>Presupuestos Asociados:</label>
                                    <ul>
                                        <?php
                                        // Query para obtener presupuestos
                                        $queryPresupuestos = "SELECT id_presupuesto, presupuesto AS nombre FROM presupuestos WHERE id_municipio = $id_municipio";
                                        $resultPresupuestos = mysqli_query($db, $queryPresupuestos);
                                        while ($presupuesto = mysqli_fetch_assoc($resultPresupuestos)) : ?>
                                            <li>
                                                <?php echo $presupuesto['nombre']; ?>
                                                <button class="delete-presupuesto" data-id="<?php echo $presupuesto['id_presupuesto']; ?>">Eliminar</button>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                </div>
                                <div class="mb-3">
                                    <label>Agregar Factura:</label>
                                    <input type="text" name="nueva_factura" class="form-control" placeholder="Nombre de factura">
                                    <label>Archivo:</label>
                                    <input type="file" name="archivo_factura">
                                </div>

                                <div class="mb-3">
                                    <label>Correo 1</label>
                                    <input type="email" name="correo1" class="form-control" value="<?php echo $municipio['correo1']; ?>">
                                </div>
                                <div class="mb-3">
                                    <label>Correo 2</label>
                                    <input type="email" name="correo2" class="form-control" value="<?php echo $municipio['correo2']; ?>">
                                </div>
                                <div class="mb-3">
                                    <label>URL del Convenio</label>
                                    <input type="url" name="convenio_url" class="form-control" value="<?php echo $municipio['convenio_url']; ?>">
                                </div>
                                <button type="submit" class="btn btn-success">Guardar Cambios</button>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
    <script src="assets/demo/chart-area-demo.js"></script>
    <script src="assets/demo/chart-bar-demo.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
    <script src="js/datatables-simple-demo.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Este código verifica si hay un mensaje en la sesión y muestra un SweetAlert2
        <?php
        if (isset($_SESSION['mensaje'])) {
            echo "
        Swal.fire({
            title: 'Aviso',
            text: '$_SESSION[mensaje]',
            icon: 'info',
            confirmButtonText: 'OK'
        });
        ";
            unset($_SESSION['mensaje']);
        }
        ?>

        const deleteButtons = document.querySelectorAll('.delete-factura');
        deleteButtons.forEach(button => {
            button.addEventListener('click', openDeleteModalFactura);
        });

        function openDeleteModalFactura(event) {
            event.preventDefault();
            const id = event.currentTarget.dataset.id;
            $('#deleteConfirmModalFactura').data('id', id).modal('show');
        }

        $('#confirmDeleteFactura').click(function() {
            $(this).prop('disabled', true); // Deshabilitar el botón al hacer click
            const id = $('#deleteConfirmModalFactura').data('id');
            const buttonClicked = document.querySelector(`.delete-factura[data-id="${id}"]`);

            $.ajax({
                url: 'modificar_municipio.php',
                type: 'POST',
                data: {
                    action: 'delete',
                    id_factura: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response && response.success) {
                        Swal.fire({
                            title: 'Eliminado!',
                            text: 'La factura ha sido eliminada.',
                            icon: 'success',
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Oops...',
                            text: 'Error al eliminar factura.',
                            icon: 'error'
                        });
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        title: 'Oops...',
                        text: 'Ocurrió un error al eliminar la factura: ' + errorThrown,
                        icon: 'error'
                    });
                    $('#confirmDeleteFactura').prop('disabled', false);
                }
            });
        });

        document.getElementById("deleteButton").addEventListener("click", function() {
            let modal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
            modal.show();
        });

        document.getElementById("confirmDelete").addEventListener("click", function() {
            let formData = new FormData();
            formData.append("id_municipio", <?php echo $municipio['id_municipio']; ?>);

            fetch("eliminar_municipio.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === "success") {
                        let modalInstance = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
                        modalInstance.hide();

                        document.getElementById("deleteSuccessAlert").style.display = "block";

                        setTimeout(function() {
                            window.location.href = 'index.php';
                        }, 500);
                    } else {
                        Swal.fire({
                            title: 'Oops...',
                            text: result.message,
                            icon: 'error'
                        });
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    Swal.fire({
                        title: 'Oops...',
                        text: 'Error al comunicarse con el servidor.',
                        icon: 'error'
                    });
                });
        });
    </script>

    <script>
        $('#confirmDeletePresupuesto').click(function() {
            const id = $('#deleteConfirmModalPresupuesto').data('id');

            $.ajax({
                url: 'modificar_municipio.php',
                type: 'POST',
                data: {
                    action: 'delete',
                    id_presupuesto: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response && response.success) {
                        Swal.fire({
                            title: 'Eliminado!',
                            text: 'El presupuesto ha sido eliminado con éxito.',
                            icon: 'success',
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Oops...',
                            text: 'Error al eliminar el presupuesto. ' + (response.message || ''),
                            icon: 'error',
                        });
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        title: 'Oops...',
                        text: 'Hubo un problema al tratar de eliminar el presupuesto: ' + errorThrown,
                        icon: 'error'
                    });
                }
            });

            $('#deleteConfirmModalPresupuesto').modal('hide');
        });

        const deleteButtonsPresupuesto = document.querySelectorAll('.delete-presupuesto');
        deleteButtonsPresupuesto.forEach(button => {
            button.addEventListener('click', openDeleteModalPresupuesto);
        });

        function openDeleteModalPresupuesto(event) {
            event.preventDefault();
            const id = event.currentTarget.dataset.id;
            $('#deleteConfirmModalPresupuesto').data('id', id).modal('show');
        }
    </script>
</body>

</html>