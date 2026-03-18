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

// Crea tabla si no existe para evitar fallos en ambientes nuevos
function ensureTables(mysqli $db) {
  $sql1 = "CREATE TABLE IF NOT EXISTS asistencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_alumno INT NOT NULL,
    fecha DATE NOT NULL,
    presente TINYINT(1) NOT NULL DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_alumno_fecha (id_alumno, fecha),
    INDEX idx_fecha (fecha),
    INDEX idx_alumno (id_alumno),
    CONSTRAINT fk_asist_alumno FOREIGN KEY (id_alumno) REFERENCES alumnos(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
  if (!$db->query($sql1)) {
    error_log('Error creando tabla asistencias: ' . $db->error);
  }
}

ensureTables($conexion);

$method = $_SERVER['REQUEST_METHOD'];

try {
  if ($method === 'GET') {
    // GET /asistencias.php?club_id=ID
    $clubId = isset($_GET['club_id']) ? (int)$_GET['club_id'] : 0;
    if ($clubId <= 0) {
      http_response_code(400);
      echo json_encode(['status' => 'error', 'message' => 'club_id requerido']);
      exit;
    }

    // Obtener alumnos del club
    $stmtA = $conexion->prepare('SELECT id, nombre, apellidoP, apellidoM FROM alumnos WHERE id_club = ? ORDER BY apellidoP ASC, apellidoM ASC, nombre ASC');
    $stmtA->bind_param('i', $clubId);
    $stmtA->execute();
    $resA = $stmtA->get_result();
    $alumnos = [];
    $alumnoIds = [];
    while ($row = $resA->fetch_assoc()) { $alumnos[] = $row; $alumnoIds[] = (int)$row['id']; }

    // Si no hay alumnos, devolver sin fechas
    if (empty($alumnoIds)) {
      echo json_encode(['status' => 'success', 'data' => ['fechas' => [], 'asistencias' => [], 'alumnos' => $alumnos]]);
      exit;
    }

    // Obtener asistencias para esos alumnos
    $placeholders = implode(',', array_fill(0, count($alumnoIds), '?'));
    $types = str_repeat('i', count($alumnoIds));
    $stmt = $conexion->prepare("SELECT id_alumno, fecha, presente FROM asistencias WHERE id_alumno IN ($placeholders) ORDER BY fecha ASC");
    
    if (!$stmt) {
      error_log("Error preparando SELECT asistencias: " . $conexion->error);
      echo json_encode(['status' => 'success', 'data' => ['fechas' => [], 'asistencias' => [], 'alumnos' => $alumnos]]);
      exit;
    }
    
    // Usar call_user_func_array para evitar problemas con spread operator
    call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $alumnoIds));
    
    if (!$stmt->execute()) {
      error_log("Error ejecutando SELECT asistencias: " . $stmt->error);
      echo json_encode(['status' => 'success', 'data' => ['fechas' => [], 'asistencias' => [], 'alumnos' => $alumnos]]);
      exit;
    }
    
    $res = $stmt->get_result();

    $fechasSet = [];
    $asistencias = []; // { id_alumno: { 'YYYY-MM-DD': bool } }
    while ($row = $res->fetch_assoc()) {
      $aid = (int)$row['id_alumno'];
      $fecha = $row['fecha']; // YYYY-MM-DD
      $pres = (int)$row['presente'] === 1;
      $fechasSet[$fecha] = true;
      if (!isset($asistencias[$aid])) $asistencias[$aid] = [];
      $asistencias[$aid][$fecha] = $pres;
    }

    // Las fechas ya están en $fechasSet desde la consulta de asistencias
    $fechas = array_keys($fechasSet);
    sort($fechas);
    
    error_log("Total de fechas encontradas: " . count($fechas));
    error_log("Fechas: " . json_encode($fechas));

    echo json_encode(['status' => 'success', 'data' => [
      'fechas' => $fechas,
      'asistencias' => $asistencias,
      'alumnos' => $alumnos
    ]]);
    exit;
  }

  if ($method === 'POST') {
    // POST body: { club_id, fecha: 'YYYY-MM-DD', registros: [{ alumno_id, presente }] }
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) $payload = [];

    $clubId = isset($payload['club_id']) ? (int)$payload['club_id'] : 0;
    $fecha = isset($payload['fecha']) ? trim((string)$payload['fecha']) : '';
    $registros = isset($payload['registros']) && is_array($payload['registros']) ? $payload['registros'] : [];

    $errors = [];
    if ($clubId <= 0) $errors[] = 'club_id requerido';
    if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) $errors[] = 'fecha requerida en formato YYYY-MM-DD';

    if (!empty($errors)) {
      http_response_code(422);
      echo json_encode(['status' => 'error', 'message' => 'Validación', 'details' => $errors]);
      exit;
    }

    // Validar que los alumno_id pertenezcan al club
    $ids = array_map(fn($r) => (int)($r['alumno_id'] ?? 0), $registros);
    $ids = array_values(array_filter(array_unique($ids), fn($v) => $v > 0));

    if (!empty($ids)) {
      $place = implode(',', array_fill(0, count($ids), '?'));
      $types = str_repeat('i', count($ids)) . 'i';
      $stmtV = $conexion->prepare("SELECT id FROM alumnos WHERE id IN ($place) AND id_club = ?");
      
      // Preparar los parámetros para bind_param
      $params = array_merge($ids, [$clubId]);
      
      // Usar call_user_func_array para evitar el error de spread operator
      call_user_func_array(
        [$stmtV, 'bind_param'],
        array_merge([$types], $params)
      );
      
      $stmtV->execute();
      $resV = $stmtV->get_result();
      $validIds = [];
      while ($row = $resV->fetch_assoc()) { $validIds[] = (int)$row['id']; }
      $validMap = array_flip($validIds);
    } else {
      $validMap = [];
    }

    // Insertar o actualizar registros de asistencia para esa fecha
    $stmtIns = $conexion->prepare('INSERT INTO asistencias (id_alumno, fecha, presente) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE presente = VALUES(presente)');

    $count = 0;
    foreach ($registros as $r) {
      $aid = (int)($r['alumno_id'] ?? 0);
      if ($aid <= 0) continue;
      if (!isset($validMap[$aid])) continue; // ignorar alumnos fuera del club
      $pres = !empty($r['presente']) ? 1 : 0;
      $stmtIns->bind_param('isi', $aid, $fecha, $pres);
      if ($stmtIns->execute()) {
        $count++;
        error_log("Asistencia guardada: id_alumno=$aid, fecha=$fecha, presente=$pres");
      } else {
        error_log("Error guardando asistencia: " . $stmtIns->error);
      }
    }

    echo json_encode(['status' => 'success', 'message' => "Registros guardados: $count"]);
    exit;}

  if ($method === 'PUT') {
    // PUT /asistencias.php  body: { alumno_id, fecha: 'YYYY-MM-DD', presente: bool }
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) $payload = [];

    $alumnoId = isset($payload['alumno_id']) ? (int)$payload['alumno_id'] : 0;
    $fecha = isset($payload['fecha']) ? trim((string)$payload['fecha']) : '';
    $presente = !empty($payload['presente']) ? 1 : 0;

    if ($alumnoId <= 0 || $fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
      http_response_code(422);
      echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
      exit;
    }

    $stmt = $conexion->prepare('INSERT INTO asistencias (id_alumno, fecha, presente) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE presente = VALUES(presente)');
    $stmt->bind_param('isi', $alumnoId, $fecha, $presente);
    if (!$stmt->execute()) {
      http_response_code(500);
      echo json_encode(['status' => 'error', 'message' => 'No se pudo guardar', 'error' => $stmt->error]);
      exit;
    }

    echo json_encode(['status' => 'success']);
    exit;
  }

  http_response_code(405);
  echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

