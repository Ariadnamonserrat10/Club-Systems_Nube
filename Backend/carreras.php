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

function seedDefaultCarreras(mysqli $conexion)
{
    $defaultCarreras = [
        'Ingeniería Civil',
        'Ingeniería Industrial',
        'Ingeniería en Sistemas Computacionales',
        'Ingeniería en Gestión Empresarial',
        'Licenciatura en Administración',
        'Licenciatura en Arquitectura',
        'Ingeniería en Mecatrónica',
    ];

    $stmtInsert = $conexion->prepare('INSERT INTO carreras (nombre) VALUES (?)');
    if (!$stmtInsert) {
        return;
    }

    foreach ($defaultCarreras as $nombreCarrera) {
        $stmtCheck = $conexion->prepare('SELECT id FROM carreras WHERE nombre = ? LIMIT 1');
        if (!$stmtCheck) {
            continue;
        }
        $stmtCheck->bind_param('s', $nombreCarrera);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();
        $exists = $resCheck && $resCheck->num_rows > 0;
        $stmtCheck->close();

        if (!$exists) {
            $stmtInsert->bind_param('s', $nombreCarrera);
            $stmtInsert->execute();
        }
    }

    $stmtInsert->close();
}

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

    if (empty($rows)) {
        seedDefaultCarreras($conexion);

        $result = $conexion->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row['id'] = (int)$row['id'];
                $rows[] = $row;
            }
        }
    }

    echo json_encode(['status' => 'success', 'data' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

