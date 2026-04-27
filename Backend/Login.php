<?php
require_once __DIR__ . "/cors.php";
header("Content-Type: application/json; charset=utf-8");

include __DIR__ . "/db.php";
require_once __DIR__ . "/validation.php";

session_start();

function checkRateLimit($usuario, $maxAttempts = 5, $windowSeconds = 300) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = "rate_{$ip}_{$usuario}";
    $now = time();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'reset' => $now + $windowSeconds];
    }
    
    if ($now > $_SESSION[$key]['reset']) {
        $_SESSION[$key] = ['count' => 0, 'reset' => $now + $windowSeconds];
    }
    
    $_SESSION[$key]['count']++;
    return $_SESSION[$key]['count'] <= $maxAttempts;
}

function constantTimeResponse($success) {
    $fakeHash = '$2y$10$FakeHashForTimingPreventionXXXXXXXX';
    if ($success) {
        echo json_encode(["status" => "success", "fake" => $fakeHash]);
    } else {
        echo json_encode(["status" => "error", "message" => "Credenciales inválidas", "fake" => $fakeHash]);
    }
    exit;
}

$rawBody = file_get_contents("php://input");
$data = json_decode($rawBody, true);

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

if (!checkRateLimit($usuario)) {
  http_response_code(429);
  echo json_encode(["status" => "error", "message" => "Demasiados intentos. Intenta de nuevo en 5 minutos"]);
  exit;
}

if (!is_username_alnum_combo_exact8($usuario, false)) {
  http_response_code(422);
  echo json_encode(["status" => "error", "message" => "El usuario debe ser alfanumérico, combinar letras y números, y tener exactamente 8 caracteres"]);
  exit;
}

if (!is_password_strong_exact8($password, false)) {
  http_response_code(422);
  echo json_encode(["status" => "error", "message" => "La contraseña debe tener exactamente 8 caracteres e incluir mayúscula, minúscula, número y carácter especial"]);
  exit;
}

if ($userType === '') {
  http_response_code(422);
  echo json_encode(["status" => "error", "message" => "Debes seleccionar un área (Oficina o Monitor)"]);
  exit;
}

$stmt = $conexion->prepare(
  "SELECT u.id, u.nombre, u.apellidoP, u.apellidoM, u.tipo, u.foto, u.password, u.club_asignado, c.nombre AS club_nombre
   FROM usuarios u
   LEFT JOIN clubs c ON u.club_asignado = c.id
   WHERE BINARY u.usuario = ?"
);
if (!$stmt) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "Error en el sistema"]);
  exit;
}

$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  http_response_code(401);
  constantTimeResponse(false);
}

$user = $result->fetch_assoc();

$passwordHash = $user['password'] ?? '';

if (!password_verify($password, $passwordHash)) {
  http_response_code(401);
  constantTimeResponse(false);
}

$userTypeDB = strtoupper($user['tipo']);
$userTypeSelected = strtoupper($userType);

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

