<?php
/**
 * AuditHelper - Enterprise-grade centralized audit logging system
 * 
 * RULES:
 * - ONLY log REAL user actions (INSERT, UPDATE, DELETE, LOGIN, LOGOUT, etc.)
 * - NEVER log auto-refresh, GET requests, polling, or background operations
 * - Deduplication: Prevent duplicate logs within a time window
 * - Security: All validation happens on backend, frontend cannot inject fake logs
 */

require_once __DIR__ . '/db.php';

class AuditHelper {
    private static $instance = null;
    private $conexion = null;
    
    const ACTION_INSERT = 'INSERTAR';
    const ACTION_UPDATE = 'ACTUALIZAR';
    const ACTION_DELETE = 'ELIMINAR';
    const ACTION_LOGIN = 'LOGIN';
    const ACTION_LOGOUT = 'LOGOUT';
    const ACTION_ATTENDANCE = 'ASISTENCIA';
    const ACTION_DOWNLOAD = 'DESCARGA';
    const ACTION_PASSWORD_CHANGE = 'CAMBIO_CONTRASENA';
    const ACTION_SECURITY = 'SEGURIDAD';
    
    const TYPE_CLUB = 'club';
    const TYPE_ALUMNO = 'alumno';
    const TYPE_USUARIO = 'usuario';
    const TYPE_ASISTENCIA = 'asistencia';
    const TYPE_EVALUACION = 'evaluacion';
    const TYPE_CONFIGURACION = 'configuracion';
    const TYPE_REPORTE = 'reporte';
    const TYPE_SESION = 'sesion';
    const TYPE_SISTEMA = 'sistema';
    
    private $allowedActions = [
        self::ACTION_INSERT,
        self::ACTION_UPDATE,
        self::ACTION_DELETE,
        self::ACTION_LOGIN,
        self::ACTION_LOGOUT,
        self::ACTION_ATTENDANCE,
        self::ACTION_DOWNLOAD,
        self::ACTION_PASSWORD_CHANGE,
        self::ACTION_SECURITY,
    ];
    
    private $allowedTypes = [
        self::TYPE_CLUB,
        self::TYPE_ALUMNO,
        self::TYPE_USUARIO,
        self::TYPE_ASISTENCIA,
        self::TYPE_EVALUACION,
        self::TYPE_CONFIGURACION,
        self::TYPE_REPORTE,
        self::TYPE_SESION,
        self::TYPE_SISTEMA,
    ];
    
    private $deduplicationWindowSeconds = 5;
    
    private function __construct() {
        global $conexion;
        if (isset($conexion) && $conexion instanceof mysqli) {
            $this->conexion = $conexion;
        }
    }
    
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function hasConnection(): bool {
        return $this->conexion !== null && $this->conexion instanceof mysqli;
    }
    
    public function log(
        int $idUsuario,
        string $usuario,
        string $accion,
        string $tipo,
        string $descripcion
    ): bool {
        if (!$this->hasConnection()) {
            return false;
        }
        
        $accion = strtoupper(trim($accion));
        $tipo = strtolower(trim($tipo));
        
        if (!$this->isValidAction($accion)) {
            error_log("AuditHelper: Invalid action '$accion' - not allowed");
            return false;
        }
        
        if (!$this->isValidType($tipo)) {
            error_log("AuditHelper: Invalid type '$tipo' - not allowed, using 'sistema'");
            $tipo = self::TYPE_SISTEMA;
        }
        
        if ($this->isDuplicate($idUsuario, $accion, $tipo, $descripcion)) {
            error_log("AuditHelper: Duplicate log detected - skipping");
            return false;
        }
        
        $usuario = $this->sanitizeUsuario($usuario);
        $descripcion = $this->sanitizeDescripcion($descripcion);
        
        $sql = "INSERT INTO auditoria (id_usuario, usuario, accion, tipo, descripcion) VALUES (?, ?, ?, ?, ?)";
        
        try {
            $stmt = $this->conexion->prepare($sql);
            if (!$stmt) {
                $this->ensureTableStructure();
                $stmt = $this->conexion->prepare($sql);
                if (!$stmt) {
                    error_log("AuditHelper: Failed to prepare statement: " . $this->conexion->error);
                    return false;
                }
            }
            
            $stmt->bind_param("issss", $idUsuario, $usuario, $accion, $tipo, $descripcion);
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Throwable $e) {
            error_log("AuditHelper: Exception during log: " . $e->getMessage());
            return false;
        }
    }
    
    public function logInsert(int $idUsuario, string $usuario, string $tipo, string $descripcion): bool {
        return $this->log($idUsuario, $usuario, self::ACTION_INSERT, $tipo, $descripcion);
    }
    
    public function logUpdate(int $idUsuario, string $usuario, string $tipo, string $descripcion): bool {
        return $this->log($idUsuario, $usuario, self::ACTION_UPDATE, $tipo, $descripcion);
    }
    
    public function logDelete(int $idUsuario, string $usuario, string $tipo, string $descripcion): bool {
        return $this->log($idUsuario, $usuario, self::ACTION_DELETE, $tipo, $descripcion);
    }
    
    public function logLogin(int $idUsuario, string $usuario, string $descripcion = ''): bool {
        $desc = $descripcion ?: "Inicio de sesión exitoso";
        return $this->log($idUsuario, $usuario, self::ACTION_LOGIN, self::TYPE_SESION, $desc);
    }
    
    public function logLogout(int $idUsuario, string $usuario, string $descripcion = ''): bool {
        $desc = $descripcion ?: "Cierre de sesión";
        return $this->log($idUsuario, $usuario, self::ACTION_LOGOUT, self::TYPE_SESION, $desc);
    }
    
    public function logAttendance(int $idUsuario, string $usuario, string $descripcion): bool {
        return $this->log($idUsuario, $usuario, self::ACTION_ATTENDANCE, self::TYPE_ASISTENCIA, $descripcion);
    }
    
    public function logDownload(int $idUsuario, string $usuario, string $descripcion): bool {
        return $this->log($idUsuario, $usuario, self::ACTION_DOWNLOAD, self::TYPE_REPORTE, $descripcion);
    }
    
    private function isDuplicate(int $idUsuario, string $accion, string $tipo, string $descripcion): bool {
        try {
            $window = $this->deduplicationWindowSeconds;
            $sql = "SELECT id FROM auditoria 
                    WHERE id_usuario = ? 
                    AND accion = ? 
                    AND tipo = ? 
                    AND descripcion = ? 
                    AND fecha >= DATE_SUB(NOW(), INTERVAL ? SECOND)
                    LIMIT 1";
            
            $stmt = $this->conexion->prepare($sql);
            if (!$stmt) {
                return false;
            }
            
            $stmt->bind_param("isssi", $idUsuario, $accion, $tipo, $descripcion, $window);
            $stmt->execute();
            $result = $stmt->get_result();
            $found = $result->num_rows > 0;
            $stmt->close();
            
            return $found;
        } catch (Throwable $e) {
            return false;
        }
    }
    
    private function isValidAction(string $accion): bool {
        return in_array($accion, $this->allowedActions);
    }
    
    private function isValidType(string $tipo): bool {
        return in_array($tipo, $this->allowedTypes);
    }
    
    private function sanitizeUsuario(string $usuario): string {
        $usuario = trim($usuario);
        if (strlen($usuario) > 100) {
            $usuario = substr($usuario, 0, 100);
        }
        return $usuario;
    }
    
    private function sanitizeDescripcion(string $descripcion): string {
        $descripcion = trim($descripcion);
        $descripcion = strip_tags($descripcion);
        $descripcion = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $descripcion);
        return $descripcion;
    }
    
    private function ensureTableStructure(): void {
        try {
            $check = $this->conexion->query("SHOW COLUMNS FROM auditoria LIKE 'usuario'");
            if (!$check || $check->num_rows === 0) {
                $this->conexion->query("ALTER TABLE auditoria ADD COLUMN usuario VARCHAR(100) NULL");
            }
            
            $check = $this->conexion->query("SHOW COLUMNS FROM auditoria LIKE 'tipo'");
            if (!$check || $check->num_rows === 0) {
                $this->conexion->query("ALTER TABLE auditoria ADD COLUMN tipo VARCHAR(50) NULL DEFAULT 'sistema'");
            }
            
            $check = $this->conexion->query("SHOW INDEX FROM auditoria WHERE Key_name = 'idx_tipo'");
            if (!$check || $check->num_rows === 0) {
                $this->conexion->query("ALTER TABLE auditoria ADD INDEX idx_tipo (tipo)");
            }
        } catch (Throwable $e) {
            error_log("AuditHelper: Failed to ensure table structure: " . $e->getMessage());
        }
    }
    
    public function getLogs(int $limit = 100, int $offset = 0): array {
        if (!$this->hasConnection()) {
            return [];
        }
        
        try {
            $sql = "SELECT a.*, u.nombre as usuario_nombre, u.apellidoP as usuario_apellido 
                    FROM auditoria a 
                    LEFT JOIN usuarios u ON a.id_usuario = u.id 
                    ORDER BY a.fecha DESC 
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->conexion->prepare($sql);
            if (!$stmt) {
                return [];
            }
            
            $stmt->bind_param("ii", $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $logs = [];
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
            
            $stmt->close();
            return $logs;
        } catch (Throwable $e) {
            return [];
        }
    }
    
    public function getTotalCount(): int {
        if (!$this->hasConnection()) {
            return 0;
        }
        
        try {
            $result = $this->conexion->query("SELECT COUNT(*) as total FROM auditoria");
            if (!$result) {
                return 0;
            }
            $row = $result->fetch_assoc();
            return (int)($row['total'] ?? 0);
        } catch (Throwable $e) {
            return 0;
        }
    }
}

function audit_log(int $idUsuario, string $usuario, string $accion, string $tipo, string $descripcion): bool {
    return AuditHelper::getInstance()->log($idUsuario, $usuario, $accion, $tipo, $descripcion);
}

function audit_insert(int $idUsuario, string $usuario, string $tipo, string $descripcion): bool {
    return AuditHelper::getInstance()->logInsert($idUsuario, $usuario, $tipo, $descripcion);
}

function audit_update(int $idUsuario, string $usuario, string $tipo, string $descripcion): bool {
    return AuditHelper::getInstance()->logUpdate($idUsuario, $usuario, $tipo, $descripcion);
}

function audit_delete(int $idUsuario, string $usuario, string $tipo, string $descripcion): bool {
    return AuditHelper::getInstance()->logDelete($idUsuario, $usuario, $tipo, $descripcion);
}

function audit_login(int $idUsuario, string $usuario, string $descripcion = ''): bool {
    return AuditHelper::getInstance()->logLogin($idUsuario, $usuario, $descripcion);
}

function audit_logout(int $idUsuario, string $usuario, string $descripcion = ''): bool {
    return AuditHelper::getInstance()->logLogout($idUsuario, $usuario, $descripcion);
}

function audit_attendance(int $idUsuario, string $usuario, string $descripcion): bool {
    return AuditHelper::getInstance()->logAttendance($idUsuario, $usuario, $descripcion);
}

function audit_download(int $idUsuario, string $usuario, string $descripcion): bool {
    return AuditHelper::getInstance()->logDownload($idUsuario, $usuario, $descripcion);
}
