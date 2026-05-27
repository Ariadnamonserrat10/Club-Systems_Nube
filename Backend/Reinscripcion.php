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

function getPeriodoActivo($pdo) {
    $stmt = $pdo->query("SELECT * FROM periodos WHERE estado = 'ACTIVO' ORDER BY id DESC LIMIT 1");
    return $stmt->fetch();
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    $periodoActivo = getPeriodoActivo($pdo);
    if (!$periodoActivo) {
        throw new Exception("No hay un periodo activo para reinscripciones.");
    }
    $periodo_id_activo = $periodoActivo['id'];

    if ($method === 'GET') {
        if ($action === 'candidatos') {
            // Buscar alumnos acreditados en cualquier periodo anterior, que no estén en el actual
            $sql = "
                SELECT a.*, c.nombre as carrera_nombre, cl.nombre as club_anterior 
                FROM alumnos a
                LEFT JOIN carreras c ON a.carrera_id = c.id
                LEFT JOIN clubs cl ON a.id_club = cl.id
                WHERE a.estado_periodo = 'ACREDITADO' 
                AND a.id IN (
                    SELECT MAX(id) FROM alumnos GROUP BY numeroControl
                )
                AND a.numeroControl NOT IN (
                    SELECT numeroControl FROM alumnos WHERE periodo_id = ?
                )
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$periodo_id_activo]);
            $candidatos = $stmt->fetchAll();
            echo json_encode(['status' => 'success', 'data' => $candidatos]);
        }
    } elseif ($method === 'POST') {
        if ($action === 'inscribir') {
            $data = json_decode(file_get_contents("php://input"), true);
            $numeroControl = $data['numeroControl'] ?? '';
            $id_club_nuevo = $data['id_club'] ?? null;
            
            if (!$numeroControl || !$id_club_nuevo) {
                throw new Exception("Faltan datos obligatorios (numeroControl, id_club)");
            }
            
            $pdo->beginTransaction();
            
            // Obtener el último registro de este alumno acreditado
            $stmt = $pdo->prepare("SELECT * FROM alumnos WHERE numeroControl = ? AND estado_periodo = 'ACREDITADO' ORDER BY id DESC LIMIT 1");
            $stmt->execute([$numeroControl]);
            $alumno = $stmt->fetch();
            
            if (!$alumno) {
                throw new Exception("Alumno no encontrado o no está acreditado para reinscribirse.");
            }
            
            // Verificar si ya está en el periodo activo
            $stmtCheck = $pdo->prepare("SELECT id FROM alumnos WHERE numeroControl = ? AND periodo_id = ?");
            $stmtCheck->execute([$numeroControl, $periodo_id_activo]);
            if ($stmtCheck->fetchColumn()) {
                throw new Exception("El alumno ya está inscrito en el periodo activo.");
            }
            
            // Verificar cupo del nuevo club
            $stmtClub = $pdo->prepare("SELECT cupo_limite, cupo_ocupado FROM clubs WHERE id = ? FOR UPDATE");
            $stmtClub->execute([$id_club_nuevo]);
            $club = $stmtClub->fetch();
            if (!$club) throw new Exception("Club no encontrado");
            if ($club['cupo_ocupado'] >= $club['cupo_limite']) {
                throw new Exception("El club seleccionado ya no tiene cupo.");
            }
            
            // Aumentar semestre (si semestre_id = 1, pasa a 2, etc.)
            $nuevo_semestre_id = min(8, $alumno['semestre_id'] + 1); // Asumiendo máximo 8 semestres o catalog
            
            // Insertar nuevo registro
            $sqlInsert = "INSERT INTO alumnos (nombre, apellidoP, apellidoM, numeroControl, telefono, carrera_id, semestre_id, id_club, fecha_registro, periodo_id, estado_periodo) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, 'ACTIVO')";
            $stmtInsert = $pdo->prepare($sqlInsert);
            $stmtInsert->execute([
                $alumno['nombre'],
                $alumno['apellidoP'],
                $alumno['apellidoM'],
                $alumno['numeroControl'],
                $alumno['telefono'],
                $alumno['carrera_id'],
                $nuevo_semestre_id,
                $id_club_nuevo,
                $periodo_id_activo
            ]);
            
            // Aumentar cupo en el club
            $stmtUpdateClub = $pdo->prepare("UPDATE clubs SET cupo_ocupado = cupo_ocupado + 1 WHERE id = ?");
            $stmtUpdateClub->execute([$id_club_nuevo]);
            
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Reinscripción exitosa.']);
        }
    }
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
