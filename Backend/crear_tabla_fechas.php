<?php
require_once "cors.php";
header("Content-Type: application/json; charset=utf-8");

include __DIR__ . "/db.php";

if (!isset($conexion) || !($conexion instanceof mysqli)) {
  if (function_exists('getMysqli') && getMysqli() instanceof mysqli) {
    $conexion = getMysqli();
  }
}

if (!isset($conexion) || !($conexion instanceof mysqli)) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'Conexión a BD no disponible']);
  exit;
}

try {
  // Crear tabla fechas_asistencia si no existe
  $sql = "CREATE TABLE IF NOT EXISTS fechas_asistencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    club_id INT NOT NULL,
    fecha DATE NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_club_fecha (club_id, fecha),
    INDEX idx_club (club_id),
    INDEX idx_fecha (fecha),
    CONSTRAINT fk_fa_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

  if ($conexion->query($sql)) {
    echo json_encode(['status' => 'success', 'message' => 'Tabla fechas_asistencia creada correctamente']);
  } else {
    echo json_encode(['status' => 'error', 'message' => 'Error al crear tabla: ' . $conexion->error]);
  }
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

