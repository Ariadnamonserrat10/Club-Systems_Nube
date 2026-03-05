<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json; charset=utf-8");

include __DIR__ . "/db.php";

$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "No se recibieron datos"]);
    exit;
}

// Normalizar campos
$nombre = trim($input['nombre'] ?? '');
$apellidoP = trim($input['apellidoP'] ?? '');
$apellidoM = trim($input['apellidoM'] ?? '');
$numeroControl = trim($input['numeroControl'] ?? '');
$telefono = trim($input['telefono'] ?? '');
$carrera_id = isset($input['carrera']) ? (int)$input['carrera'] : null;
$semestre_id = isset($input['semestre']) ? (int)$input['semestre'] : null;
$usuario = trim($input['usuario'] ?? '');
$password_raw = (string)($input['password'] ?? '');
$tipo = strtoupper(trim($input['tipo'] ?? 'OFICINA'));
$club_asignado = isset($input['club_asignado']) ? (int)$input['club_asignado'] : null;
$foto = trim($input['foto'] ?? null);

// Validaciones básicas
if ($nombre === '' || $apellidoP === '' || $usuario === '' || $password_raw === '') {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Faltan campos requeridos"]);
    exit;
}

// Verificar duplicados: usuario
$q = "SELECT id FROM usuarios WHERE usuario = ?";
$stmtc = $conexion->prepare($q);
$stmtc->bind_param("s", $usuario);
$stmtc->execute();
$stmtc->store_result();
if ($stmtc->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Nombre de usuario ya registrado"]);
    exit;
}

// Verificar duplicados: número de control (si aplica)
if ($numeroControl !== '') {
    $q2 = "SELECT id FROM usuarios WHERE numeroControl = ?";
    $stmtc2 = $conexion->prepare($q2);
    $stmtc2->bind_param("s", $numeroControl);
    $stmtc2->execute();
    $stmtc2->store_result();
    if ($stmtc2->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Número de control ya registrado"]);
        exit;
    }
}

// Hashear contraseña
$password_hash = password_hash($password_raw, PASSWORD_BCRYPT);

// Normalizar valores nulos
$telefono_val = $telefono !== '' ? $telefono : null;
$carrera_val = $carrera_id !== null ? $carrera_id : null;
$semestre_val = $semestre_id !== null ? $semestre_id : null;
$club_val = $club_asignado !== null ? $club_asignado : null;
$foto_val = $foto !== '' ? $foto : null;

/////////////////////////////////////////////////////////////////////////////////////
//   SEPARACIÓN DE CASOS SEGÚN TIPO DE USUARIO
/////////////////////////////////////////////////////////////////////////////////////

if ($tipo === 'OFICINA') {

    //////////////////////////////////////////////////////
    // CASO 1: USUARIO TIPO OFICINA
    //////////////////////////////////////////////////////

    $query = "INSERT INTO usuarios 
        (nombre, apellidoP, apellidoM, usuario, password, tipo, foto)
        VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conexion->prepare($query);

    $stmt->bind_param(
        "sssssss",
        $nombre,
        $apellidoP,
        $apellidoM,
        $usuario,
        $password_hash,
        $tipo,
        $foto_val
    );

} else {

    //////////////////////////////////////////////////////
    // CASO 2: MONITOR / ALUMNO / OTROS TIPOS
    //////////////////////////////////////////////////////

    $query = "INSERT INTO usuarios 
        (nombre, apellidoP, apellidoM, numeroControl, telefono, carrera_id, semestre_id, usuario, password, tipo, club_asignado, foto)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conexion->prepare($query);

    $stmt->bind_param(
        "sssssiisssis",
        $nombre,
        $apellidoP,
        $apellidoM,
        $numeroControl,
        $telefono_val,
        $carrera_val,
        $semestre_val,
        $usuario,
        $password_hash,
        $tipo,
        $club_val,
        $foto_val
    );
}

/////////////////////////////////////////////////////////////////////////////////////
//   EJECUCIÓN DE LA CONSULTA
/////////////////////////////////////////////////////////////////////////////////////

try {
    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Usuario registrado exitosamente",
            "id" => $stmt->insert_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Error al registrar usuario: " . $stmt->error]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Excepción: " . $e->getMessage()]);
}

?>
