-- Script para la creación de tablas de evaluación y auditoría
-- Base de datos: sistema_clubs

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
    observaciones TEXT,
    valor_numerico TINYINT NOT NULL,
    nivel_desempeno TINYINT NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    usuario VARCHAR(100) NULL,
    accion VARCHAR(100) NOT NULL,
    tipo VARCHAR(50) NULL DEFAULT 'sistema',
    descripcion TEXT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario (id_usuario),
    INDEX idx_fecha (fecha),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
