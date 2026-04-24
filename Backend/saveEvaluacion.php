<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include __DIR__ . "/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
    exit;
}

$required = [
    'nombre_estudiante', 'nombre_club', 'periodo_realizacion',
    'criterio_1', 'criterio_2', 'criterio_3', 'criterio_4',
    'criterio_5', 'criterio_6', 'criterio_7',
    'valor_numerico', 'nivel_desempeno'
];

foreach ($required as $field) {
    if (!isset($data[$field])) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => "El campo $field es requerido"]);
        exit;
    }
}

try {
    $sql = "INSERT INTO evaluaciones (
        nombre_estudiante, nombre_club, periodo_realizacion, 
        criterio_1, criterio_2, criterio_3, criterio_4, 
        criterio_5, criterio_6, criterio_7, 
        observaciones, valor_numerico, nivel_desempeno
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param(
        "sssiiiiiiisii",
        $data['nombre_estudiante'],
        $data['nombre_club'],
        $data['periodo_realizacion'],
        $data['criterio_1'],
        $data['criterio_2'],
        $data['criterio_3'],
        $data['criterio_4'],
        $data['criterio_5'],
        $data['criterio_6'],
        $data['criterio_7'],
        $data['observaciones'],
        $data['valor_numerico'],
        $data['nivel_desempeno']
    );

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Evaluación guardada correctamente']);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error al guardar evaluación: ' . $e->getMessage()]);
}
?>
