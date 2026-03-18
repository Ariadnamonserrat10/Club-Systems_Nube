<?php
$host = '35.222.207.41';
$db   = 'sistema_clubs';
$user = 'root';
$pass = 'Admin1234';
$port = 3306;

$conexion = new mysqli($host, $user, $pass, $db, $port);
$conexion->set_charset('utf8mb4');

if ($conexion->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Conexión fallida: ' . $conexion->connect_error]));
}

function getMysqli() {
    global $conexion;
    return $conexion;
}
?>
