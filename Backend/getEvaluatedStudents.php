<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . "/db.php";

$clubName = isset($_GET['club_name']) ? trim((string)$_GET['club_name']) : '';

if ($clubName === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Nombre del club es requerido']);
    exit;
}

try {
    // Obtenemos solo los nombres de los estudiantes ya evaluados en este club
    $sql = "SELECT DISTINCT nombre_estudiante FROM evaluaciones WHERE nombre_club = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $clubName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $evaluados = [];
    while ($row = $result->fetch_assoc()) {
        $evaluados[] = $row['nombre_estudiante'];
    }

    echo json_encode(['status' => 'success', 'data' => $evaluados]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error al obtener evaluados: ' . $e->getMessage()]);
}
?>
