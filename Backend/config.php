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
    echo json_encode(['status' => 'error', 'message' => 'Conexion a BD no disponible']);
    exit;
}

/**
 * Asegura que la tabla app_config exista
 */
function asegurarTablaConfig(mysqli $db)
{
    $sql = "CREATE TABLE IF NOT EXISTS `app_config` (
      `clave` varchar(50) NOT NULL,
      `valor` text DEFAULT NULL,
      `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`clave`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $db->query($sql);
}

try {
    asegurarTablaConfig($conexion);
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        if (isset($_GET['clave']) && trim((string)$_GET['clave']) !== '') {
            $clave = trim((string)$_GET['clave']);
            $stmt = $conexion->prepare('SELECT clave, valor, actualizado_en FROM app_config WHERE clave = ? LIMIT 1');
            $stmt->bind_param('s', $clave);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            echo json_encode(['status' => 'success', 'data' => $row ?: null]);
            exit;
        }

        $rows = [];
        $res = $conexion->query('SELECT clave, valor, actualizado_en FROM app_config ORDER BY clave ASC');
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
        }

        echo json_encode(['status' => 'success', 'data' => $rows]);
        exit;
    }

    if ($method === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $clave = isset($payload['clave']) ? trim((string)$payload['clave']) : '';
        $valor = array_key_exists('valor', $payload) ? (string)$payload['valor'] : '';

        if ($clave === '') {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Validacion', 'details' => ['clave es requerida']]);
            exit;
        }

        $stmt = $conexion->prepare('INSERT INTO app_config (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)');
        $stmt->bind_param('ss', $clave, $valor);
        if (!$stmt->execute()) {
            throw new RuntimeException('No se pudo guardar configuracion: ' . $stmt->error);
        }

        echo json_encode(['status' => 'success', 'ok' => true, 'clave' => $clave, 'valor' => $valor]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metodo no permitido']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
