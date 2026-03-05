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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

$usuarioId = $_GET['id'] ?? null;

if (!$usuarioId) {
  http_response_code(400);
  echo json_encode(["status" => "error", "message" => "ID de usuario requerido"]);
  exit;
}

$query = "SELECT u.id, u.nombre, u.apellidoP, u.apellidoM, u.usuario, u.tipo, u.foto, u.club_asignado, c.nombre AS club_nombre
          FROM usuarios u
          LEFT JOIN clubs c ON u.club_asignado = c.id
          WHERE u.id = ?";
$stmt = $conexion->prepare($query);
if (!$stmt) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "Error en query: " . $conexion->error]);
  exit;
}

$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  http_response_code(404);
  echo json_encode(["status" => "error", "message" => "Usuario no encontrado"]);
  exit;
}

$usuario = $result->fetch_assoc();

echo json_encode([
  "status" => "success",
  "data" => [
    "id" => (int)$usuario['id'],
    "nombre" => $usuario['nombre'],
    "apellidoP" => $usuario['apellidoP'],
    "apellidoM" => $usuario['apellidoM'],
    "usuario" => $usuario['usuario'],
    "tipo" => $usuario['tipo'],
    "foto" => $usuario['foto'],
    "club_asignado" => $usuario['club_asignado'] !== null ? (int)$usuario['club_asignado'] : null,
    "club_nombre" => $usuario['club_nombre'] ?? null
  ]
]);
?>