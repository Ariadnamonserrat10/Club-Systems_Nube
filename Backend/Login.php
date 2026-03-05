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
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
  http_response_code(400);
  echo json_encode(["status" => "error", "message" => "No se recibieron datos"]);
  exit;
}

$usuario = trim($data["usuario"] ?? "");
$password = (string)($data["password"] ?? $data["contrasena"] ?? "");
$userType = trim($data["userType"] ?? "");

if ($usuario === '' || $password === '') {
  http_response_code(422);
  echo json_encode(["status" => "error", "message" => "Usuario y contraseña requeridos"]);
  exit;
}

if ($userType === '') {
  http_response_code(422);
  echo json_encode(["status" => "error", "message" => "Debes seleccionar un área (Oficina o Monitor)"]);
  exit;
}

// Seleccionar también club_asignado y nombre del club (si existe)
$stmt = $conexion->prepare(
  "SELECT u.id, u.nombre, u.apellidoP, u.apellidoM, u.tipo, u.foto, u.password, u.club_asignado, c.nombre AS club_nombre
   FROM usuarios u
   LEFT JOIN clubs c ON u.club_asignado = c.id
   WHERE BINARY u.usuario = ?"
);
if (!$stmt) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "Error en BD: " . $conexion->error]);
  exit;
}

$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  http_response_code(401);
  echo json_encode(["status" => "error", "message" => "El usuario no existe"]);
  exit;
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {
  http_response_code(401);
  echo json_encode(["status" => "error", "message" => "La contraseña es incorrecta"]);
  exit;
}

// Validar que el tipo de usuario seleccionado coincida con el tipo en la BD
$userTypeDB = strtoupper($user['tipo']);
$userTypeSelected = strtoupper($userType);

// Mapear valores: "oficina" -> "OFICINA", "monitor" -> "MONITOR"
if ($userTypeSelected === "OFICINA") {
  $userTypeSelected = "OFICINA";
} elseif ($userTypeSelected === "MONITOR") {
  $userTypeSelected = "MONITOR";
}

if ($userTypeDB !== $userTypeSelected) {
  http_response_code(403);
  echo json_encode(["status" => "error", "message" => "Este usuario es de tipo " . strtolower($userTypeDB) . ", pero seleccionaste " . strtolower($userTypeSelected) . ". Por favor, selecciona el área correcta."]);
  exit;
}

echo json_encode([
  "status" => "success",
  "message" => "Login exitoso",
  "id" => (int)$user['id'],
  "nombre" => $user['nombre'],
  "apellidoP" => $user['apellidoP'],
  "apellidoM" => $user['apellidoM'],
  "tipo" => $user['tipo'],
  "foto" => $user['foto'],
  "club_asignado" => $user['club_asignado'] !== null ? (int)$user['club_asignado'] : null,
  "club_nombre" => $user['club_nombre'] ?? null
]);
?>
