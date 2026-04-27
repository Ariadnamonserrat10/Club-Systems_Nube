-- =====================================================
-- SISTEMA DE GESTIÓN DE CLUBS ESTUDIANTILES
-- Estructura de Base de Datos Completa
-- =====================================================

-- =====================================================
-- 1. TABLA USUARIOS (corregida)
-- =====================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellidoP VARCHAR(50) NOT NULL,
    apellidoM VARCHAR(50) NOT NULL,
    numeroControl CHAR(8) NULL,
    telefono CHAR(15) NULL,
    carrera_id INT NULL,
    semestre_id INT NULL,
    usuario VARCHAR(30) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    tipo ENUM('OFICINA', 'MONITOR') NOT NULL,
    club_asignado INT NULL,
    foto VARCHAR(255) NULL,
   created_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario),
    INDEX idx_tipo (tipo),
    INDEX idx_club_asignado (club_asignado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 2. TABLA PERIODOS
-- =====================================================
CREATE TABLE IF NOT EXISTS periodos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    estado ENUM('ACTIVO', 'CERRADO') NOT NULL DEFAULT 'ACTIVO',
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 3. TABLA CLUBS
-- =====================================================
CREATE TABLE IF NOT EXISTS clubs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('CULTURAL', 'DEPORTIVO') NOT NULL DEFAULT 'CULTURAL',
    descripcion VARCHAR(255) NULL,
   cupo_limite INT NULL,
    id_responsable INT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tipo (tipo),
    INDEX idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 4. TABLA ALUMNOS
-- =====================================================
CREATE TABLE IF NOT EXISTS alumnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellidoP VARCHAR(50) NOT NULL,
    apellidoM VARCHAR(50) NOT NULL,
    numeroControl CHAR(8) NULL,
    telefono CHAR(15) NULL,
    carrera_id INT NULL,
    semestre_id INT NULL,
    id_club INT NULL,
    periodo_id INT NOT NULL,
    estado_periodo ENUM('ACTIVO', 'ACREDITADO', 'REPROBADO') NOT NULL DEFAULT 'ACTIVO',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_numeroControl (numeroControl),
    INDEX idx_periodo (periodo_id),
    INDEX idx_club (id_club),
    UNIQUE KEY uq_alumno_periodo (numeroControl, periodo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 5. TABLA CARRERAS
-- =====================================================
CREATE TABLE IF NOT EXISTS carreras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    abreviatura VARCHAR(20) NULL,
    INDEX idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 6. TABLA SEMESTRES
-- =====================================================
CREATE TABLE IF NOT EXISTS semestres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero INT NOT NULL,
    INDEX idx_numero (numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 7. TABLA ASISTENCIAS
-- =====================================================
CREATE TABLE IF NOT EXISTS asistentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_alumno INT NOT NULL,
    id_fecha INT NOT NULL,
    presente TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_alumno (id_alumno),
    INDEX idx_fecha (id_fecha),
    UNIQUE KEY uq_asistencia (id_alumno, id_fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 8. TABLA FECHAS_ASISTENCIAS
-- =====================================================
CREATE TABLE IF NOT EXISTS fechas_asistencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_club INT NOT NULL,
    fecha DATE NOT NULL,
    INDEX idx_club (id_club),
    INDEX idx_fecha (fecha),
    UNIQUE KEY uq_fecha_club (fecha, id_club)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 9. TABLA EVALUACIONES
-- =====================================================
CREATE TABLE IF NOT EXISTS evaluaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_estudiante VARCHAR(255) NOT NULL,
    nombre_club VARCHAR(255) NOT NULL,
    periodo_realizacion DATE NOT NULL,
    criterio_1 TINYINT NOT NULL,
    criterio_2 TINYINT NOT NULL,
    criterio_3 TINYINT NOT NULL,
    criterio_4 TINYINT NOT NULL,
    criterio_5 TINYINT NOT NULL,
    criterio_6 TINYINT NOT NULL,
    criterio_7 TINYINT NOT NULL,
    observaciones TEXT NULL,
    valor_numerico TINYINT NOT NULL,
    nivel_desempeno TINYINT NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_estudiante (nombre_estudiante),
    INDEX idx_club (nombre_club)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 10. TABLA AUDITORIA
-- =====================================================
CREATE TABLE IF NOT EXISTS auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    accion VARCHAR(100) NOT NULL,
    descripcion TEXT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (id_usuario),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 11. TABLA CONFIGURACIÓN
-- =====================================================
CREATE TABLE IF NOT EXISTS app_config (
    clave VARCHAR(50) PRIMARY KEY,
    valor TEXT NULL,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 12. TABLA HISTORIAL CONFIGURACIÓN
-- =====================================================
CREATE TABLE IF NOT EXISTS historial_configuracion (
    periodo_id INT NOT NULL,
    clave VARCHAR(50) NOT NULL,
    valor TEXT NULL,
    PRIMARY KEY (periodo_id, clave),
    INDEX idx_periodo (periodo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 13. TABLA FIRMAS (firmas autorizadas)
-- =====================================================
CREATE TABLE IF NOT EXISTS firmas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cargo VARCHAR(50) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    INDEX idx_cargo (cargo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- 14. INSERTAR DATOS INICIALES
-- =====================================================

-- Insertar carreras iniciales
INSERT IGNORE INTO carreras (id, nombre, abreviatura) VALUES
(1, 'Ingeniería Civil', 'I.CIV.'),
(2, 'Ingeniería Industrial', 'I.IND.'),
(3, 'Ingeniería en Sistemas Computacionales', 'I.S.C.'),
(4, 'Ingeniería en Gestión Empresarial', 'I.G.E.'),
(5, 'Licenciatura en Administración', 'L.A.'),
(6, 'Licenciatura en Arquitectura', 'ARQ.'),
(7, 'Ingeniería en Mecatrónica', 'I.MEC.');

-- Insertar semestres
INSERT IGNORE INTO semestres (id, numero) VALUES
(1,1), (2,2), (3,3), (4,4), (5,5), (6,6), (7,7), (8,8), (9,9), (10,10), (11,11), (12,12);

-- Insertar un periodo inicial
INSERT IGNORE INTO periodos (id, nombre, fecha_inicio, fecha_fin, estado) VALUES
(1, 'Enero - Junio 2025', '2025-01-01', '2025-06-30', 'ACTIVO');

-- Insertar usuario administrador por defecto (password: Admin123!)
-- Password hasheado con bcrypt
INSERT IGNORE INTO usuarios (id, nombre, apellidoP, apellidoM, usuario, password, tipo) VALUES
(1, 'Administrador', 'Sistema', 'Admin', 'ADM00001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'OFICINA');

-- =====================================================
-- 15. CREAR USUARIO DE PRUEBA PARA LOGIN
-- =====================================================
-- Este usuario puede usarse para probar: usuario "TEST0001", password "Test123!"
INSERT IGNORE INTO usuarios (id, nombre, apellidoP, apellidoM, usuario, password, tipo) VALUES
(2, 'Monitor', 'Prueba', 'Test', 'TEST0001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'MONITOR');