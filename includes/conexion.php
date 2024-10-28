<?php
// Conexion con la base de datos usando MySQLi
$server = '127.0.0.1';
$username = 'u957245339_ramcc';
$password = 'Desarrollo2023';
$database = 'u957245339_administracion';
$db = mysqli_connect($server, $username, $password, $database);

mysqli_query($db, "SET NAMES 'utf8'");
// Verificar si la conexión fue exitosa
if (!isset($_SESSION)) {
	session_start();
}
