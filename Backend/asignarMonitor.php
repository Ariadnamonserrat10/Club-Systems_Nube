<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . "/db.php";

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['monitor_id']) || !isset($data['club_id'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "monitor_id y club_id son requeridos"]);
    exit;
}

$monitor_id = (int)$data['monitor_id'];
$club_id = (int)$data['club_id'];

// Verificar que el monitor existe y es tipo MONITOR
$stmtMonitor = $conexion->prepare("SELECT id FROM usuarios WHERE id = ? AND tipo = 'MONITOR'");
$stmtMonitor->bind_param("i", $monitor_id);
$stmtMonitor->execute();
$resultMonitor = $stmtMonitor->get_result();

if ($resultMonitor->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Monitor no encontrado"]);
    exit;
}
$stmtMonitor->close();

// Verificar que el club existe
$stmtClub = $conexion->prepare("SELECT id FROM clubs WHERE id = ?");
$stmtClub->bind_param("i", $club_id);
$stmtClub->execute();
$resultClub = $stmtClub->get_result();

if ($resultClub->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Club no encontrado"]);
    exit;
}
$stmtClub->close();

// Actualizar el monitor con el club asignado
$stmtUpdate = $conexion->prepare("UPDATE usuarios SET club_asignado = ? WHERE id = ?");
$stmtUpdate->bind_param("ii", $club_id, $monitor_id);

if ($stmtUpdate->execute()) {
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Monitor asignado al club correctamente",
        "data" => [
            "monitor_id" => $monitor_id,
            "club_id" => $club_id
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al asignar monitor: " . $stmtUpdate->error]);
}
$stmtUpdate->close();
?>
