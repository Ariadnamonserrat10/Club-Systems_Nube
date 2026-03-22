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

function ensureConfigTable(mysqli $db)
{
    $sql = "CREATE TABLE IF NOT EXISTS app_config (
        clave VARCHAR(100) NOT NULL PRIMARY KEY,
        valor TEXT NULL,
        actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!$db->query($sql)) {
        throw new RuntimeException('No se pudo asegurar app_config: ' . $db->error);
    }
}

function getConfigValue(mysqli $db, string $clave): ?string
{
    $stmt = $db->prepare('SELECT valor FROM app_config WHERE clave = ? LIMIT 1');
    $stmt->bind_param('s', $clave);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    if (!$row) {
        return null;
    }
    return isset($row['valor']) ? (string)$row['valor'] : null;
}

function getUsuarioById(mysqli $db, int $id): ?array
{
    $stmt = $db->prepare('SELECT id, nombre, apellidoP, apellidoM FROM usuarios WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    if (!$row) {
        return null;
    }

    $nombre = trim(((string)($row['nombre'] ?? '')) . ' ' . ((string)($row['apellidoP'] ?? '')) . ' ' . ((string)($row['apellidoM'] ?? '')));

    return [
        'id' => (int)$row['id'],
        'nombre' => $nombre,
    ];
}

try {
    ensureConfigTable($conexion);
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $firmaJefeActividades = getConfigValue($conexion, 'firma_jefe_actividades');
        $firmaJefePromocion = getConfigValue($conexion, 'firma_jefe_promocion');
        $firmaJefaServicios = getConfigValue($conexion, 'firma_jefa_servicios');

        $result = [
            'jefe_actividades' => null,
            'jefe_promocion' => null,
            'jefa_servicios' => null,
        ];

        if ($firmaJefeActividades !== null && ctype_digit((string)$firmaJefeActividades)) {
            $u = getUsuarioById($conexion, (int)$firmaJefeActividades);
            if ($u) {
                $result['jefe_actividades'] = $u;
            }
        }

        if ($firmaJefePromocion !== null && ctype_digit((string)$firmaJefePromocion)) {
            $u = getUsuarioById($conexion, (int)$firmaJefePromocion);
            if ($u) {
                $result['jefe_promocion'] = $u;
            }
        }

        if ($firmaJefaServicios !== null && trim((string)$firmaJefaServicios) !== '') {
            $result['jefa_servicios'] = [
                'nombre' => trim((string)$firmaJefaServicios),
            ];
        }

        echo json_encode($result);
        exit;
    }

    if ($method === 'POST') {
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $cargo = isset($payload['cargo']) ? trim((string)$payload['cargo']) : '';
        $idUsuario = isset($payload['id_usuario']) ? (int)$payload['id_usuario'] : 0;

        if (!in_array($cargo, ['jefe_actividades', 'jefe_promocion'], true)) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Validacion', 'details' => ['cargo invalido']]);
            exit;
        }

        if ($idUsuario <= 0) {
            http_response_code(422);
            echo json_encode(['status' => 'error', 'message' => 'Validacion', 'details' => ['id_usuario invalido']]);
            exit;
        }

        $existe = getUsuarioById($conexion, $idUsuario);
        if (!$existe) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Usuario no encontrado']);
            exit;
        }

        $clave = 'firma_' . $cargo;
        $valor = (string)$idUsuario;
        $stmt = $conexion->prepare('INSERT INTO app_config (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)');
        $stmt->bind_param('ss', $clave, $valor);
        if (!$stmt->execute()) {
            throw new RuntimeException('No se pudo guardar firma: ' . $stmt->error);
        }

        echo json_encode(['status' => 'success', 'ok' => true, 'cargo' => $cargo, 'id_usuario' => $idUsuario]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metodo no permitido']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
