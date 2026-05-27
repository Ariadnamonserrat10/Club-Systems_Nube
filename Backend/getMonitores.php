<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . "/db.php";

// Obtener el club_id desde parámetros GET
$club_id = isset($_GET['club_id']) ? (int)$_GET['club_id'] : null;

if (!$club_id) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "club_id es requerido"]);
    exit;
}

// Verificar que el club existe
$queryClubExists = "SELECT id FROM clubs WHERE id = ?";
$stmtClubExists = $conexion->prepare($queryClubExists);
$stmtClubExists->bind_param("i", $club_id);
$stmtClubExists->execute();
$resultClubExists = $stmtClubExists->get_result();

if ($resultClubExists->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Club no encontrado"]);
    exit;
}
$stmtClubExists->close();

// Buscar monitores asignados a este club por club_asignado en usuarios
$queryMonitores = "SELECT id, nombre, apellidoP, apellidoM, usuario FROM usuarios WHERE tipo = 'MONITOR' AND club_asignado = ? ORDER BY nombre ASC, apellidoP ASC";
$stmtMonitores = $conexion->prepare($queryMonitores);
$stmtMonitores->bind_param("i", $club_id);
$stmtMonitores->execute();
$resultMonitores = $stmtMonitores->get_result();

$monitores = [];
while ($monitor = $resultMonitores->fetch_assoc()) {
    $monitores[] = [
        "id" => (int)$monitor["id"],
        "nombre" => $monitor["nombre"],
        "apellidoP" => $monitor["apellidoP"],
        "apellidoM" => $monitor["apellidoM"],
        "usuario" => $monitor["usuario"]
    ];
}
$stmtMonitores->close();

http_response_code(200);
echo json_encode([
    "status" => "success",
    "data" => [
        "club_id" => $club_id,
        "monitores" => $monitores
    ]
]);
?>


