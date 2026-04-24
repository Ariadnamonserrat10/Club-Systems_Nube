<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . "/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['id_usuario']) || !isset($data['accion'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Datos insuficientes para auditoría']);
    exit;
}

try {
    $sql = "INSERT INTO auditoria (id_usuario, accion, descripcion) VALUES (?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $descripcion = isset($data['descripcion']) ? $data['descripcion'] : null;
    $stmt->bind_param("iss", $data['id_usuario'], $data['accion'], $descripcion);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Auditoría registrada']);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error en auditoría: ' . $e->getMessage()]);
}
?>
