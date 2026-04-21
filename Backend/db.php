<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "sistema_clubs";
$port = 3306;

$conexion = new mysqli($host, $user, $pass, $dbname, $port);

if ($conexion->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "Error de conexión: " . $conexion->connect_error]));
}

$conexion->set_charset("utf8mb4");

$conn = $conexion;

if (!function_exists('getMysqli')) {
    function getMysqli() {
        global $conexion;
        return $conexion instanceof mysqli ? $conexion : null;
    }
}
?>