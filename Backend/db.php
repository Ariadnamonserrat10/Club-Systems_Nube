<?php
$host = getenv('DB_HOST') ?: '35.222.207.41';
$db   = getenv('DB_NAME') ?: 'sistema_clubs';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'Admin1234';
$port = (int)(getenv('DB_PORT') ?: 3306);
$connectTimeout = (int)(getenv('DB_CONNECT_TIMEOUT') ?: 5);

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

function getMysqli() {
    global $conexion;
    return $conexion;
}
?>
