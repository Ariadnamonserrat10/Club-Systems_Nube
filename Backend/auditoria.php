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

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Obtener registros de auditoría
        $sql = "SELECT a.*, u.nombre as usuario_nombre 
               FROM auditoria a 
               LEFT JOIN usuarios u ON a.id_usuario = u.id 
               ORDER BY a.fecha DESC 
               LIMIT 100";
        $stmt = $conexion->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $registros = [];
        while ($row = $result->fetch_assoc()) {
            $registros[] = $row;
        }
        
        echo json_encode(['status' => 'success', 'data' => $registros]);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!$data || !isset($data['accion'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Datos insuficientes']);
            exit;
        }
        
        $id_usuario = isset($data['id_usuario']) ? (int)$data['id_usuario'] : null;
        $usuario = isset($data['usuario']) ? $data['usuario'] : null;
        $accion = $data['accion'];
        $tipo = isset($data['tipo']) ? $data['tipo'] : 'sistema';
        $descripcion = isset($data['descripcion']) ? $data['descripcion'] : null;
        
        $sql = "INSERT INTO auditoria (id_usuario, usuario, accion, tipo, descripcion) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("issss", $id_usuario, $usuario, $accion, $tipo, $descripcion);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'id' => $conexion->insert_id]);
        } else {
            throw new Exception($stmt->error);
        }
        exit;
    }
    
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>