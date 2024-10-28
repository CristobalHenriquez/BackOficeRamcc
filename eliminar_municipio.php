<?php
session_start();
require_once 'includes/conexion.php';

// Iniciar respuesta como array para devolver información más detallada
$response = [
    'status' => 'error',
    'message' => 'Error desconocido.'
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['id_municipio'])) {
        $id_municipio = $_POST['id_municipio'];

        $stmt = $db->prepare("DELETE FROM municipios WHERE id_municipio = ?");
        $stmt->bind_param("i", $id_municipio);

        if ($stmt->execute()) {
            $response['status'] = "success";
            $response['message'] = "Eliminado con éxito";
        } else {
            $response['message'] = "Error al ejecutar consulta: " . $stmt->error;
        }
    } else {
        $response['message'] = "No se proporcionó el id_municipio.";
    }
} else {
    $response['message'] = "Método de solicitud incorrecto.";
}

// Devuelve la respuesta en formato JSON
echo json_encode($response);
