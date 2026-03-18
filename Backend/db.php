<?php
$host = getenv('DB_HOST') ?: '35.222.207.41';
$db   = getenv('DB_NAME') ?: 'sistema_clubs';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'Admin1234';
$port = (int)(getenv('DB_PORT') ?: 3306);

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
