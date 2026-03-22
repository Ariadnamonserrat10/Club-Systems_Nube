<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
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

function normalizaDesempenoPorFaltas(int $faltas): array
{
    if ($faltas <= 1) {
        return ['desempeno' => 'EXCELENTE', 'nivel_desempeno' => 5, 'valor_numerico' => 5];
    }
    if ($faltas === 2) {
        return ['desempeno' => 'BUENO', 'nivel_desempeno' => 3, 'valor_numerico' => 3];
    }
    return ['desempeno' => 'REGULAR', 'nivel_desempeno' => 2, 'valor_numerico' => 2];
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Metodo no permitido']);
        exit;
    }

    $nombreEstudiante = isset($_GET['nombre_estudiante']) ? trim((string)$_GET['nombre_estudiante']) : '';
    $nombreClub = isset($_GET['nombre_club']) ? trim((string)$_GET['nombre_club']) : '';

    if ($nombreEstudiante === '' || $nombreClub === '') {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Validacion', 'details' => ['nombre_estudiante y nombre_club son requeridos']]);
        exit;
    }

    $sqlAlumno = "
      SELECT a.id
      FROM alumnos a
      INNER JOIN clubs c ON c.id = a.id_club
      WHERE TRIM(UPPER(CONCAT_WS(' ', a.nombre, a.apellidoP, COALESCE(a.apellidoM, '')))) = TRIM(UPPER(?))
        AND TRIM(UPPER(c.nombre)) = TRIM(UPPER(?))
      LIMIT 1
    ";

    $stmt = $conexion->prepare($sqlAlumno);
    $stmt->bind_param('ss', $nombreEstudiante, $nombreClub);
    $stmt->execute();
    $res = $stmt->get_result();
    $alumno = $res->fetch_assoc();

    if (!$alumno) {
        echo json_encode([]);
        exit;
    }

    $idAlumno = (int)$alumno['id'];
    $faltas = 0;

    $stmtF = $conexion->prepare('SELECT COUNT(*) AS faltas FROM asistencias WHERE id_alumno = ? AND presente = 0');
    if ($stmtF) {
        $stmtF->bind_param('i', $idAlumno);
        $stmtF->execute();
        $resF = $stmtF->get_result();
        $rowF = $resF->fetch_assoc();
        if ($rowF && isset($rowF['faltas'])) {
            $faltas = (int)$rowF['faltas'];
        }
    }

    $map = normalizaDesempenoPorFaltas($faltas);
    echo json_encode([
        'id_alumno' => $idAlumno,
        'faltas' => $faltas,
        'desempeno' => $map['desempeno'],
        'nivel_desempeno' => $map['nivel_desempeno'],
        'valor_numerico' => $map['valor_numerico'],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
