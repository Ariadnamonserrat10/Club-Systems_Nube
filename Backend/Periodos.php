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

// Función para obtener periodo activo
function getPeriodoActivo($pdo) {
    $stmt = $pdo->query("SELECT * FROM periodos WHERE estado = 'ACTIVO' ORDER BY id DESC LIMIT 1");
    return $stmt->fetch();
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    if ($method === 'GET') {
        if ($action === 'activo') {
            $periodo = getPeriodoActivo($pdo);
            if ($periodo) {
                echo json_encode(['status' => 'success', 'data' => $periodo]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No hay periodo activo']);
            }
        } else {
            // Listar todos los periodos
            $stmt = $pdo->query("SELECT * FROM periodos ORDER BY id DESC");
            $periodos = $stmt->fetchAll();
            echo json_encode(['status' => 'success', 'data' => $periodos]);
        }
    } elseif ($method === 'POST') {
        if ($action === 'cerrar') {
            // 1. Obtener periodo activo
            $periodoActivo = getPeriodoActivo($pdo);
            if (!$periodoActivo) {
                throw new Exception("No hay periodo activo para cerrar");
            }
            $periodo_id = $periodoActivo['id'];

            $pdo->beginTransaction();

            try {
                // 2. Evaluar asistencias y marcar alumnos
                $stmtAlumnos = $pdo->prepare("SELECT id FROM alumnos WHERE periodo_id = ? AND estado_periodo = 'ACTIVO'");
                $stmtAlumnos->execute([$periodo_id]);
                $alumnos = $stmtAlumnos->fetchAll();

                $stmtUpdateAlumno = $pdo->prepare("UPDATE alumnos SET estado_periodo = ? WHERE id = ?");

                foreach ($alumnos as $alumno) {
                    $idAlumno = $alumno['id'];
                    $stmtFaltas = $pdo->prepare("SELECT COUNT(*) as faltas FROM asistencias WHERE id_alumno = ? AND presente = 0");
                    $stmtFaltas->execute([$idAlumno]);
                    $faltas = $stmtFaltas->fetchColumn();

                    // Regla: 3 faltas o más -> REPROBADO, menos de 3 -> ACREDITADO
                    $estado = ($faltas >= 3) ? 'REPROBADO' : 'ACREDITADO';
                    $stmtUpdateAlumno->execute([$estado, $idAlumno]);
                }

                // 3. Guardar copia de configuración actual en historial
                $stmtConfig = $pdo->query("SELECT clave, valor FROM app_config");
                $configs = $stmtConfig->fetchAll();
                
                $stmtInsertHistorial = $pdo->prepare("INSERT INTO historial_configuracion (periodo_id, clave, valor) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
                foreach ($configs as $conf) {
                    $stmtInsertHistorial->execute([$periodo_id, $conf['clave'], $conf['valor']]);
                }

                // 4. Cerrar periodo
                $stmtCerrar = $pdo->prepare("UPDATE periodos SET estado = 'CERRADO' WHERE id = ?");
                $stmtCerrar->execute([$periodo_id]);

                // 5. Generar automáticamente un nuevo periodo
                // Determinar nuevo nombre y fechas (Ej: si era Ene-Jun, ahora es Ago-Dic)
                $nombreAnterior = $periodoActivo['nombre'];
                $nuevoNombre = "Nuevo Periodo (Generado Auto)";
                
                // Lógica simple de nombres:
                if (strpos($nombreAnterior, 'Enero') !== false || strpos($nombreAnterior, 'Ene') !== false) {
                    $year = (int)date('Y', strtotime($periodoActivo['fecha_inicio']));
                    $nuevoNombre = "Agosto - Diciembre $year";
                    $nuevaFechaInicio = "$year-08-01";
                    $nuevaFechaFin = "$year-12-15";
                } else if (strpos($nombreAnterior, 'Agosto') !== false || strpos($nombreAnterior, 'Ago') !== false) {
                    $year = (int)date('Y', strtotime($periodoActivo['fecha_inicio'])) + 1;
                    $nuevoNombre = "Enero - Junio $year";
                    $nuevaFechaInicio = "$year-01-01";
                    $nuevaFechaFin = "$year-06-15";
                } else {
                    $year = (int)date('Y');
                    $nuevoNombre = "Periodo " . ($year + 1);
                    $nuevaFechaInicio = date('Y-m-d');
                    $nuevaFechaFin = date('Y-m-d', strtotime('+6 months'));
                }

                $stmtNuevo = $pdo->prepare("INSERT INTO periodos (nombre, fecha_inicio, fecha_fin, estado) VALUES (?, ?, ?, 'ACTIVO')");
                $stmtNuevo->execute([$nuevoNombre, $nuevaFechaInicio, $nuevaFechaFin]);
                $nuevo_periodo_id = $pdo->lastInsertId();

                // Opcional: Limpiar cupos ocupados en clubs
                $pdo->exec("UPDATE clubs SET cupo_ocupado = 0");

                $pdo->commit();
                
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Periodo cerrado y nuevo periodo generado con éxito',
                    'nuevo_periodo_id' => $nuevo_periodo_id
                ]);

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

        } else if ($action === 'actualizar_historial') {
            // Actualizar configuración histórica (para constancias pasadas)
            $data = json_decode(file_get_contents("php://input"), true);
            $periodo_id = $data['periodo_id'] ?? null;
            $clave = $data['clave'] ?? null;
            $valor = $data['valor'] ?? null;

            if (!$periodo_id || !$clave) {
                throw new Exception("Faltan datos");
            }
            
            $stmt = $pdo->prepare("UPDATE historial_configuracion SET valor = ? WHERE periodo_id = ? AND clave = ?");
            $stmt->execute([$valor, $periodo_id, $clave]);
            echo json_encode(['status' => 'success']);
            
        } else {
            // Crear periodo manualmente (opcional, ya que se auto-genera)
            $data = json_decode(file_get_contents("php://input"), true);
            $nombre = $data['nombre'] ?? '';
            $fecha_inicio = $data['fecha_inicio'] ?? '';
            $fecha_fin = $data['fecha_fin'] ?? '';

            if (!$nombre || !$fecha_inicio || !$fecha_fin) {
                throw new Exception("Faltan campos obligatorios");
            }

            // Cerrar cualquier periodo activo existente primero
            $pdo->exec("UPDATE periodos SET estado = 'CERRADO' WHERE estado = 'ACTIVO'");

            $stmt = $pdo->prepare("INSERT INTO periodos (nombre, fecha_inicio, fecha_fin, estado) VALUES (?, ?, ?, 'ACTIVO')");
            $stmt->execute([$nombre, $fecha_inicio, $fecha_fin]);

            echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
