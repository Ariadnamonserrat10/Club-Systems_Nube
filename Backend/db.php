<?php
$host = "localhost";
$db   = "sistema_clubs";
$user = "root";
$pass = "";
$port = 3306;
$connectTimeout = 5;

mysqli_report(MYSQLI_REPORT_OFF);
$conexion = mysqli_init();

if (!$conexion) {
    http_response_code(500);
    die(json_encode(['status' => 'error', 'message' => 'No se pudo inicializar la conexión a la base de datos.']));
}

$conexion->options(MYSQLI_OPT_CONNECT_TIMEOUT, $connectTimeout);
$connected = $conexion->real_connect($host, $user, $pass, $db, $port);

if (!$connected) {
    error_log('DB connection failed to ' . $host . ':' . $port . ' - ' . $conexion->connect_error);
    http_response_code(500);
    die(json_encode([
        'status' => 'error',
        'message' => 'No se pudo conectar a la base de datos. Verifica host, usuario, contraseña y reglas de acceso remoto.'
    ]));
}

$conexion->set_charset('utf8mb4');

if (!function_exists('getMysqli')) {
    function getMysqli() {
        global $conexion;
        return $conexion;
    }
}
?>
