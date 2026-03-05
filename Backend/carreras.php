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
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Conexión a BD no disponible']);
    exit;
}

try {
    $sql = "SELECT id, nombre FROM carreras ORDER BY id ASC";
    $result = $conexion->query($sql);

    $rows = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Normalizar tipos: id como entero
            $row['id'] = (int)$row['id'];
            $rows[] = $row;
        }
    }

    echo json_encode(['status' => 'success', 'data' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
