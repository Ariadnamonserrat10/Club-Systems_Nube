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

$method = $_SERVER['REQUEST_METHOD'];

try {
  if ($method === 'GET') {
    // GET /Usuarios.php  ó  /Usuarios.php?id=ID
    $rows = [];
    if (isset($_GET['id']) && $_GET['id'] !== '') {
      $id = (int)$_GET['id'];
      $stmt = $conexion->prepare('SELECT id, nombre, apellidoP, apellidoM, usuario, tipo, numeroControl, telefono, carrera_id, semestre_id, club_asignado, foto FROM usuarios WHERE id = ?');
      $stmt->bind_param('i', $id);
      $stmt->execute();
      $res = $stmt->get_result();
      $row = $res->fetch_assoc();
      if (!$row) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
        exit;
      }
      echo json_encode(['status' => 'success', 'data' => $row]);
      exit;
    } else {
      $sql = 'SELECT id, nombre, apellidoP, apellidoM, usuario, tipo, numeroControl, telefono, carrera_id, semestre_id, club_asignado, foto FROM usuarios ORDER BY tipo ASC, apellidoP ASC, apellidoM ASC, nombre ASC';
      if ($res = $conexion->query($sql)) {
        while ($row = $res->fetch_assoc()) { $rows[] = $row; }
      }
      echo json_encode(['status' => 'success', 'data' => $rows]);
      exit;
    }
  }

  if ($method === 'PUT') {
    // PUT /Usuarios.php?id=ID  body: JSON con campos a actualizar; si incluye password, se re-hashea
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
      http_response_code(400);
      echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
      exit;
    }

    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) $payload = [];

    // Obtener actual para validar duplicados y defaults
    $stmtCur = $conexion->prepare('SELECT id, usuario, numeroControl FROM usuarios WHERE id = ?');
    $stmtCur->bind_param('i', $id);
    $stmtCur->execute();
    $resCur = $stmtCur->get_result();
    $cur = $resCur->fetch_assoc();
    if (!$cur) {
      http_response_code(404);
      echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
      exit;
    }

    $fields = [];
    $types = '';
    $values = [];

    // Campos actualizables
    $map = [
      'nombre' => 's',
      'apellidoP' => 's',
      'apellidoM' => 's',
      'usuario' => 's',
      'tipo' => 's', // Debe ser 'OFICINA' o 'MONITOR'
      'numeroControl' => 's',
      'telefono' => 's', // puede ser null
      'carrera_id' => 'i', // puede ser null
      'semestre_id' => 'i', // puede ser null
      'club_asignado' => 'i', // puede ser null
      'foto' => 's', // filename o URL relativa
    ];

    foreach ($map as $k => $t) {
      if (array_key_exists($k, $payload)) {
        $fields[] = "$k = ?";
        $types .= $t;
        // normalizar nulls
        $val = $payload[$k];
        if ($val === '' || $val === null) {
          $val = null;
        }
        $values[] = $val;
      }
    }

    // Cambio de contraseña si viene "password"
    if (!empty($payload['password'])) {
      $pwdHash = password_hash((string)$payload['password'], PASSWORD_BCRYPT);
      $fields[] = 'password = ?';
      $types .= 's';
      $values[] = $pwdHash;
    }

    if (empty($fields)) {
      http_response_code(400);
      echo json_encode(['status' => 'error', 'message' => 'No hay campos para actualizar']);
      exit;
    }

    // Validación de duplicados para usuario
    if (array_key_exists('usuario', $payload)) {
      $usuario = trim((string)$payload['usuario']);
      $stmtU = $conexion->prepare('SELECT id FROM usuarios WHERE usuario = ? AND id <> ? LIMIT 1');
      $stmtU->bind_param('si', $usuario, $id);
      $stmtU->execute();
      $stmtU->store_result();
      if ($stmtU->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Nombre de usuario ya registrado']);
        exit;
      }
    }

    // Validación de duplicados para numeroControl si viene
    if (array_key_exists('numeroControl', $payload) && $payload['numeroControl'] !== '') {
      $nc = trim((string)$payload['numeroControl']);
      $stmtNC = $conexion->prepare('SELECT id FROM usuarios WHERE numeroControl = ? AND id <> ? LIMIT 1');
      $stmtNC->bind_param('si', $nc, $id);
      $stmtNC->execute();
      $stmtNC->store_result();
      if ($stmtNC->num_rows > 0) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => 'Número de control ya registrado']);
        exit;
      }
    }

    $sql = 'UPDATE usuarios SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $types .= 'i';
    $values[] = $id;

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param($types, ...$values);
    if (!$stmt->execute()) {
      http_response_code(500);
      echo json_encode(['status' => 'error', 'message' => 'No se pudo actualizar', 'error' => $stmt->error]);
      exit;
    }

    echo json_encode(['status' => 'success']);
    exit;
  }

  if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
      http_response_code(400);
      echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
      exit;
    }
    $stmt = $conexion->prepare('DELETE FROM usuarios WHERE id = ?');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) {
      http_response_code(500);
      echo json_encode(['status' => 'error', 'message' => 'No se pudo eliminar', 'error' => $stmt->error]);
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
