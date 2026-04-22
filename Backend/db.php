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

// Compatibilidad con PDO (Requerido por Clubs.php y otros)
try {
    $dsn = "mysql:host=$host;dbname=$db;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $GLOBALS['pdo'] = $pdo;
} catch (PDOException $e) {
    error_log('PDO connection failed: ' . $e->getMessage());
    // No matamos el proceso porque mysqli puede seguir funcionando
}

if (!function_exists('getMysqli')) {
    function getMysqli() {
        global $conexion;
        return $conexion;
    }
}
?>
