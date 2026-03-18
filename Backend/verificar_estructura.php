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
  $info = [
    'tablas' => [],
    'estructura_asistencias' => [],
    'estructura_alumnos' => [],
    'datos_muestra' => []
  ];
  
  // Listar todas las tablas
  $result = $conexion->query("SHOW TABLES");
  while ($row = $result->fetch_row()) {
    $info['tablas'][] = $row[0];
  }
  
  // Estructura de asistencias
  $result = $conexion->query("DESCRIBE asistencias");
  if ($result) {
    while ($row = $result->fetch_assoc()) {
      $info['estructura_asistencias'][] = $row;
    }
  }
  
  // Estructura de alumnos
  $result = $conexion->query("DESCRIBE alumnos");
  if ($result) {
    while ($row = $result->fetch_assoc()) {
      $info['estructura_alumnos'][] = $row;
    }
  }
  
  // Datos de muestra de asistencias
  $result = $conexion->query("SELECT * FROM asistencias LIMIT 5");
  if ($result) {
    while ($row = $result->fetch_assoc()) {
      $info['datos_muestra'][] = $row;
    }
  }
  
  echo json_encode(['status' => 'success', 'data' => $info], JSON_PRETTY_PRINT);
  
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>

