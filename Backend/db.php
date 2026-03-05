<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$host = "clubsystem.mysql.database.azure.com";
$user = "clubadmin";
$pass = "Computo2026";
$dbname = "sistema_clubs";
$port = 3306;

$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, __DIR__ . "/DigiCertGlobalRootCA.crt.pem", NULL, NULL);
$conexion = mysqli_real_connect($conn, $host, $user, $pass, $dbname, $port, MYSQLI_CLIENT_SSL);

if (!$conexion) {
    http_response_code(500);
    die(json_encode(["error" => "Error de conexión: " . mysqli_connect_error()]));
}

$conn->set_charset("utf8mb4");

$conn = $conexion;

if (!function_exists('getMysqli')) {
    function getMysqli() {
        global $conexion;
        return $conexion instanceof mysqli ? $conexion : null;
    }
}
?>