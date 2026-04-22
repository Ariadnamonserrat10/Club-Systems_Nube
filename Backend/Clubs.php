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
require_once __DIR__ . "/validation.php";

// Obtener conexión PDO o mysqli desde db.php
$pdo = null;
if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
  $pdo = $GLOBALS['pdo'];
} elseif (function_exists('getConnection')) {
  // Algunas implementaciones de getConnection no existen o no devuelven PDO.
  try {
    $connectionFactory = 'getConnection';
    $maybePdo = $connectionFactory();
    if ($maybePdo instanceof PDO) {
      $pdo = $maybePdo;
    }
  } catch (Throwable $e) {
    error_log('Clubs.php: getConnection() falló, se usa mysqli: ' . $e->getMessage());
    $pdo = null;
  }
}

if (!$pdo) {
  // Fallback principal: db.php expone mysqli vía getMysqli()/$conexion.
  if (isset($GLOBALS['mysqli']) && $GLOBALS['mysqli'] instanceof mysqli) {
    $mysqli = $GLOBALS['mysqli'];
  } elseif (function_exists('getMysqli') && getMysqli() instanceof mysqli) {
    $mysqli = getMysqli();
  } elseif (isset($GLOBALS['conexion']) && $GLOBALS['conexion'] instanceof mysqli) {
    $mysqli = $GLOBALS['conexion'];
  } elseif (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
    $mysqli = $GLOBALS['conn'];
  }

  if (!isset($mysqli)) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo obtener una conexión a la base de datos']);
    exit;
  }

  // Envoltorio mínimo para operaciones con mysqli
  handleWithMysqli($mysqli);
  exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
  switch ($method) {
    case 'GET':
      // Listar clubs
      $stmt = $pdo->query('SELECT c.id, c.nombre, c.tipo, c.descripcion, c.cupo_limite, (SELECT COUNT(*) FROM alumnos a JOIN periodos p ON a.periodo_id = p.id WHERE a.id_club = c.id AND p.estado = \'ACTIVO\') AS cupo_ocupado, c.id_responsable, c.creado_en FROM clubs c ORDER BY c.id DESC');
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
      echo json_encode(['data' => $rows]);
      break;

    case 'POST':
      $payload = json_decode(file_get_contents('php://input'), true);
      if (!is_array($payload)) $payload = [];

      $nombre = isset($payload['nombre']) ? to_title_case($payload['nombre']) : '';
      $descripcion = isset($payload['descripcion']) ? to_title_case($payload['descripcion']) : null;
      $cupo_limite = null;
      if (array_key_exists('cupo_limite', $payload)) {
        $cupoRaw = filter_var($payload['cupo_limite'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 50]]);
        if ($cupoRaw !== false) {
          $cupo_limite = (int)$cupoRaw;
        }
      }
      $id_responsable = isset($payload['id_responsable']) && $payload['id_responsable'] !== ''
        ? (int)$payload['id_responsable']
        : null;

      $errors = [];
      if ($nombre === '' || mb_strlen($nombre) > 100) {
        $errors[] = 'El nombre es requerido y debe tener máximo 100 caracteres';
      }
      if ($nombre !== '' && (!is_text_only($nombre) || !starts_with_uppercase_letter($nombre))) {
        $errors[] = 'El nombre del club solo debe contener letras y espacios, e iniciar con mayúscula';
      }
      if (!is_title_case_text($nombre)) {
        $errors[] = 'El nombre del club debe tener formato correcto: solo primera letra mayúscula por palabra';
      }
      if ($descripcion === null || $descripcion === '' || !is_text_only($descripcion) || !is_title_case_text($descripcion)) {
        $errors[] = 'La descripción del club debe ser solo texto, con primera letra mayúscula';
      }
      if (!is_int($cupo_limite)) {
        $errors[] = 'cupo_limite es requerido y debe ser un entero entre 1 y 50';
      }
      if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['error' => 'Validación', 'details' => $errors]);
        break;
      }

      $tipo = isset($payload['tipo']) ? strtoupper($payload['tipo']) : 'CULTURAL';
      if (!in_array($tipo, ['CULTURAL', 'DEPORTIVO'])) {
        $tipo = 'CULTURAL';
      }

      $sql = 'INSERT INTO clubs (nombre, tipo, descripcion, cupo_limite, id_responsable) VALUES (:nombre, :tipo, :descripcion, :cupo_limite, :id_responsable)';
      $stmt = $pdo->prepare($sql);
      $stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
      $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
      $stmt->bindValue(':descripcion', $descripcion, $descripcion === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
      $stmt->bindValue(':cupo_limite', $cupo_limite, PDO::PARAM_INT);
      if ($id_responsable === null) {
        $stmt->bindValue(':id_responsable', null, PDO::PARAM_NULL);
      } else {
        $stmt->bindValue(':id_responsable', $id_responsable, PDO::PARAM_INT);
      }
      $stmt->execute();

      $id = (int)$pdo->lastInsertId();
      $stmt = $pdo->prepare('SELECT c.id, c.nombre, c.tipo, c.descripcion, c.cupo_limite, (SELECT COUNT(*) FROM alumnos a JOIN periodos p ON a.periodo_id = p.id WHERE a.id_club = c.id AND p.estado = \'ACTIVO\') AS cupo_ocupado, c.id_responsable, c.creado_en FROM clubs c WHERE c.id = :id');
      $stmt->bindValue(':id', $id, PDO::PARAM_INT);
      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      http_response_code(201);
      echo json_encode(['data' => $row]);
      break;

    case 'PUT':
      if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Falta parámetro id']);
        break;
      }
      $id = (int)$_GET['id'];
      $payload = json_decode(file_get_contents('php://input'), true);
      if (!is_array($payload)) $payload = [];

      $fields = [];
      $params = [];

      if (isset($payload['nombre'])) {
        $nombre = to_title_case((string)$payload['nombre']);
        if ($nombre === '' || mb_strlen($nombre) > 100) {
          http_response_code(422);
          echo json_encode(['error' => 'Validación', 'details' => ['El nombre es requerido y debe tener máximo 100 caracteres']]);
          break;
        }
        if (!is_text_only($nombre) || !starts_with_uppercase_letter($nombre)) {
          http_response_code(422);
          echo json_encode(['error' => 'Validación', 'details' => ['El nombre del club solo debe contener letras y espacios, e iniciar con mayúscula']]);
          break;
        }
        $fields[] = 'nombre = :nombre';
        $params[':nombre'] = [$nombre, PDO::PARAM_STR];
      }
      if (isset($payload['tipo'])) {
        $tipo = strtoupper((string)$payload['tipo']);
        if (!in_array($tipo, ['CULTURAL', 'DEPORTIVO'])) {
          $tipo = 'CULTURAL';
        }
        $fields[] = 'tipo = :tipo';
        $params[':tipo'] = [$tipo, PDO::PARAM_STR];
      }
      if (array_key_exists('descripcion', $payload)) {
        $descripcion = to_title_case((string)$payload['descripcion']);
        if ($descripcion === '' || !is_text_only($descripcion) || !is_title_case_text($descripcion)) {
          http_response_code(422);
          echo json_encode(['error' => 'Validación', 'details' => ['La descripción del club debe ser solo texto, con primera letra mayúscula']]);
          break;
        }
        $fields[] = 'descripcion = :descripcion';
        $params[':descripcion'] = [$descripcion, $descripcion === null ? PDO::PARAM_NULL : PDO::PARAM_STR];
      }
      if (isset($payload['cupo_limite'])) {
        $cupoRaw = filter_var($payload['cupo_limite'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 50]]);
        if ($cupoRaw === false) {
          http_response_code(422);
          echo json_encode(['error' => 'Validación', 'details' => ['cupo_limite debe ser un entero entre 1 y 50']]);
          break;
        }
        $cupo_limite = (int)$cupoRaw;
        $fields[] = 'cupo_limite = :cupo_limite';
        $params[':cupo_limite'] = [$cupo_limite, PDO::PARAM_INT];
      }
      if (array_key_exists('id_responsable', $payload)) {
        if ($payload['id_responsable'] === null || $payload['id_responsable'] === '') {
          $fields[] = 'id_responsable = :id_responsable';
          $params[':id_responsable'] = [null, PDO::PARAM_NULL];
        } else {
          $fields[] = 'id_responsable = :id_responsable';
          $params[':id_responsable'] = [(int)$payload['id_responsable'], PDO::PARAM_INT];
        }
      }

      if (empty($fields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No hay campos para actualizar']);
        break;
      }

      $sql = 'UPDATE clubs SET ' . implode(', ', $fields) . ' WHERE id = :id';
      $stmt = $pdo->prepare($sql);
      foreach ($params as $k => [$v, $type]) {
        $stmt->bindValue($k, $v, $type);
      }
      $stmt->bindValue(':id', $id, PDO::PARAM_INT);
      $stmt->execute();

      $stmt = $pdo->prepare('SELECT c.id, c.nombre, c.tipo, c.descripcion, c.cupo_limite, (SELECT COUNT(*) FROM alumnos a JOIN periodos p ON a.periodo_id = p.id WHERE a.id_club = c.id AND p.estado = \'ACTIVO\') AS cupo_ocupado, c.id_responsable, c.creado_en FROM clubs c WHERE c.id = :id');
      $stmt->bindValue(':id', $id, PDO::PARAM_INT);
      $stmt->execute();
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      echo json_encode(['data' => $row]);
      break;

    case 'DELETE':
      if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Falta parámetro id']);
        break;
      }
      $id = (int)$_GET['id'];
      $stmt = $pdo->prepare('DELETE FROM clubs WHERE id = :id');
      $stmt->bindValue(':id', $id, PDO::PARAM_INT);
      $stmt->execute();
      echo json_encode(['ok' => true]);
      break;

    default:
      http_response_code(405);
      echo json_encode(['error' => 'Método no permitido']);
      break;
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Excepción', 'message' => $e->getMessage()]);
}

function handleWithMysqli(mysqli $mysqli)
{
  $method = $_SERVER['REQUEST_METHOD'];
  try {
    switch ($method) {
      case 'GET':
        $res = $mysqli->query('SELECT c.id, c.nombre, c.tipo, c.descripcion, c.cupo_limite, (SELECT COUNT(*) FROM alumnos a JOIN periodos p ON a.periodo_id = p.id WHERE a.id_club = c.id AND p.estado = \'ACTIVO\') AS cupo_ocupado, c.id_responsable, c.creado_en FROM clubs c ORDER BY c.id DESC');
        $rows = [];
        if ($res) {
          while ($row = $res->fetch_assoc()) { $rows[] = $row; }
        }
        echo json_encode(['data' => $rows]);
        break;

      case 'POST':
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) $payload = [];
        $nombre = isset($payload['nombre']) ? to_title_case($payload['nombre']) : '';
        $descripcion = isset($payload['descripcion']) ? to_title_case($payload['descripcion']) : null;
        $cupo_limite = null;
        if (array_key_exists('cupo_limite', $payload)) {
          $cupoRaw = filter_var($payload['cupo_limite'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 50]]);
          if ($cupoRaw !== false) {
            $cupo_limite = (int)$cupoRaw;
          }
        }
        $id_responsable = isset($payload['id_responsable']) && $payload['id_responsable'] !== ''
          ? (int)$payload['id_responsable']
          : null;
        $errors = [];
        if ($nombre === '' || mb_strlen($nombre) > 100) {
          $errors[] = 'El nombre es requerido y debe tener máximo 100 caracteres';
        }
        if ($nombre !== '' && (!is_text_only($nombre) || !starts_with_uppercase_letter($nombre))) {
          $errors[] = 'El nombre del club solo debe contener letras y espacios, e iniciar con mayúscula';
        }
        if (!is_title_case_text($nombre)) {
          $errors[] = 'El nombre del club debe tener formato correcto: solo primera letra mayúscula por palabra';
        }
        if ($descripcion === null || $descripcion === '' || !is_text_only($descripcion) || !is_title_case_text($descripcion)) {
          $errors[] = 'La descripción del club debe ser solo texto, con primera letra mayúscula';
        }
        if (!is_int($cupo_limite)) {
          $errors[] = 'cupo_limite es requerido y debe ser un entero entre 1 y 50';
        }
        if (!empty($errors)) {
          http_response_code(422);
          echo json_encode(['error' => 'Validación', 'details' => $errors]);
          break;
        }

        $tipo = isset($payload['tipo']) ? strtoupper($payload['tipo']) : 'CULTURAL';
        if (!in_array($tipo, ['CULTURAL', 'DEPORTIVO'])) {
          $tipo = 'CULTURAL';
        }

        $stmt = $mysqli->prepare('INSERT INTO clubs (nombre, tipo, descripcion, cupo_limite, id_responsable) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('sssii', $nombre, $tipo, $descripcion, $cupo_limite, $id_responsable);
        // Si descripcion o id_responsable son null, mysqli requiere manejo especial
        if ($descripcion === null || $id_responsable === null) {
          // Reconstruir bind evitando warnings; usar tipos dinámicos
          $stmt->close();
          $stmt = $mysqli->prepare('INSERT INTO clubs (nombre, tipo, descripcion, cupo_limite, id_responsable) VALUES (?, ?, ?, ?, ?)');
          $desc = $descripcion; $resp = $id_responsable; $cupo = $cupo_limite; $t = $tipo;
          $stmt->bind_param('sssii', $nombre, $t, $desc, $cupo, $resp);
        }
        $stmt->execute();
        $id = $mysqli->insert_id;

        $stmt = $mysqli->prepare('SELECT id, nombre, tipo, descripcion, cupo_limite, id_responsable, creado_en FROM clubs WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        http_response_code(201);
        echo json_encode(['data' => $row]);
        break;

      case 'PUT':
        if (!isset($_GET['id'])) {
          http_response_code(400);
          echo json_encode(['error' => 'Falta parámetro id']);
          break;
        }
        $id = (int)$_GET['id'];
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) $payload = [];

        $fields = [];
        $types = '';
        $values = [];

        if (isset($payload['nombre'])) {
          $nombre = to_title_case((string)$payload['nombre']);
          if ($nombre === '' || mb_strlen($nombre) > 100) {
            http_response_code(422);
            echo json_encode(['error' => 'Validación', 'details' => ['El nombre es requerido y debe tener máximo 100 caracteres']]);
            break;
          }
          if (!is_text_only($nombre) || !starts_with_uppercase_letter($nombre)) {
            http_response_code(422);
            echo json_encode(['error' => 'Validación', 'details' => ['El nombre del club solo debe contener letras y espacios, e iniciar con mayúscula']]);
            break;
          }
          $fields[] = 'nombre = ?';
          $types .= 's';
          $values[] = $nombre;
        }
        if (isset($payload['tipo'])) {
          $tipo = strtoupper((string)$payload['tipo']);
          if (!in_array($tipo, ['CULTURAL', 'DEPORTIVO'])) {
            $tipo = 'CULTURAL';
          }
          $fields[] = 'tipo = ?';
          $types .= 's';
          $values[] = $tipo;
        }
        if (array_key_exists('descripcion', $payload)) {
          $descripcion = to_title_case((string)$payload['descripcion']);
          if ($descripcion === '' || !is_text_only($descripcion) || !is_title_case_text($descripcion)) {
            http_response_code(422);
            echo json_encode(['error' => 'Validación', 'details' => ['La descripción del club debe ser solo texto, con primera letra mayúscula']]);
            break;
          }
          $fields[] = 'descripcion = ?';
          $types .= 's';
          $values[] = $descripcion;
        }
        if (isset($payload['cupo_limite'])) {
          $cupoRaw = filter_var($payload['cupo_limite'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 50]]);
          if ($cupoRaw === false) {
            http_response_code(422);
            echo json_encode(['error' => 'Validación', 'details' => ['cupo_limite debe ser un entero entre 1 y 50']]);
            break;
          }
          $cupo_limite = (int)$cupoRaw;
          $fields[] = 'cupo_limite = ?';
          $types .= 'i';
          $values[] = $cupo_limite;
        }
        if (array_key_exists('id_responsable', $payload)) {
          $id_responsable = $payload['id_responsable'] === '' ? null : $payload['id_responsable'];
          $fields[] = 'id_responsable = ?';
          $types .= 'i';
          $values[] = $id_responsable;
        }

        if (empty($fields)) {
          http_response_code(400);
          echo json_encode(['error' => 'No hay campos para actualizar']);
          break;
        }

        $sql = 'UPDATE clubs SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $types .= 'i';
        $values[] = $id;

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        $stmt = $mysqli->prepare('SELECT c.id, c.nombre, c.tipo, c.descripcion, c.cupo_limite, (SELECT COUNT(*) FROM alumnos a JOIN periodos p ON a.periodo_id = p.id WHERE a.id_club = c.id AND p.estado = \'ACTIVO\') AS cupo_ocupado, c.id_responsable, c.creado_en FROM clubs c WHERE c.id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        echo json_encode(['data' => $row]);
        break;

      case 'DELETE':
        if (!isset($_GET['id'])) {
          http_response_code(400);
          echo json_encode(['error' => 'Falta parámetro id']);
          break;
        }
        $id = (int)$_GET['id'];
        $stmt = $mysqli->prepare('DELETE FROM clubs WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        echo json_encode(['ok' => true]);
        break;

      default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
        break;
    }
  } catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Excepción', 'message' => $e->getMessage()]);
  }
}

