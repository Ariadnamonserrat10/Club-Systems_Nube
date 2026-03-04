<?php
$host = "clubsystem.mysql.database.azure.com";
$user = "clubadmin";
$pass = "Computo2026";
$dbname = "sistema_clubs";
$port = 3306;

$conexion = new mysqli($host, $user, $pass, $dbname, $port);
$conexion->ssl_set(NULL, NULL, NULL, NULL, NULL);

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