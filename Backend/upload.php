<?php
require_once "cors.php";
header("Content-Type: application/json; charset=utf-8");

include __DIR__ . "/db.php";

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadDir)) {
  @mkdir($uploadDir, 0775, true);
}

if (!isset($_FILES['foto'])) {
  http_response_code(400);
  echo json_encode(['status' => 'error', 'message' => 'Archivo "foto" requerido']);
  exit;
}

$file = $_FILES['foto'];
if ($file['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400);
  echo json_encode(['status' => 'error', 'message' => 'Error al subir archivo', 'code' => $file['error']]);
  exit;
}

$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
if (!isset($allowed[$file['type']])) {
  http_response_code(415);
  echo json_encode(['status' => 'error', 'message' => 'Tipo no permitido']);
  exit;
}

if ($file['size'] > 2 * 1024 * 1024) { // 2MB
  http_response_code(413);
  echo json_encode(['status' => 'error', 'message' => 'Archivo demasiado grande (máx 2MB)']);
  exit;
}

$ext = $allowed[$file['type']];
$base = bin2hex(random_bytes(8));
$name = $base . '.' . $ext;
$dest = $uploadDir . DIRECTORY_SEPARATOR . $name;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'No se pudo guardar el archivo']);
  exit;
}

// Devolver ruta relativa para usar en la app
$relative = 'uploads/' . $name;

echo json_encode(['status' => 'success', 'file' => $relative, 'filename' => $name]);

