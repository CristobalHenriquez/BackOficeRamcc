<?php
require_once 'includes/conexion.php';

function registrarUsuario($db, $nombre, $apellido, $email, $password)
{
    // Cifrar la contraseña
    $password_segura = password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]);

    // Query preparada
    $stmt = $db->prepare("INSERT INTO usuarios (nombre, apellido, email, password) VALUES (?, ?, ?, ?)");

    // Vincular parámetros
    $stmt->bind_param("ssss", $nombre, $apellido, $email, $password_segura);

    // Ejecutar consulta
    $result = $stmt->execute();
    $stmt->close();

    return $result;
}
// Función para validar el usuario y la contraseña en la base de datos
function validarUsuario($db, $email, $password)
{
    $query = "SELECT * FROM usuarios WHERE email = ?";
    $stmt = $db->prepare($query);
    if ($stmt === false) {
        die('Error al preparar la consulta: ' . $db->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $stmt->close();

    if ($usuario && password_verify($password, $usuario['password'])) {
        return $usuario;
    }

    return false;
}

// Función para redirigir al usuario al formulario de inicio de sesión
function redirigirLogin()
{
    header("Location: login.php");
    exit();
}

// Función para redirigir al usuario al formulario de registro
function redirigirRegistro()
{
    header("Location: register.php");
    exit();
}

// Función para redirigir al usuario a la página de inicio después del inicio de sesión exitoso
function redirigirInicio()
{
    header("Location: index.php");
    exit();
}
