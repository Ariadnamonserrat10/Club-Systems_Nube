-- Tabla de periodos
CREATE TABLE IF NOT EXISTS `periodos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estado` enum('ACTIVO','CERRADO') NOT NULL DEFAULT 'ACTIVO',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Modificaciones a la tabla alumnos
-- Quitar restricción única anterior si existe
ALTER TABLE `alumnos` DROP INDEX `numeroControl`;

-- Agregar columnas de periodo
ALTER TABLE `alumnos` ADD COLUMN `periodo_id` int(11) DEFAULT NULL;
ALTER TABLE `alumnos` ADD COLUMN `estado_periodo` enum('ACTIVO','ACREDITADO','REPROBADO') NOT NULL DEFAULT 'ACTIVO';

-- Crear nueva restricción única para (numeroControl, periodo_id)
ALTER TABLE `alumnos` ADD UNIQUE KEY `uniq_alumno_periodo` (`numeroControl`, `periodo_id`);

-- Crear clave foránea a periodos
ALTER TABLE `alumnos` ADD CONSTRAINT `fk_alumnos_periodo` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`);

-- Tabla de historial de configuración (firmas)
CREATE TABLE IF NOT EXISTS `historial_configuracion` (
  `periodo_id` int(11) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `valor` text DEFAULT NULL,
  PRIMARY KEY (`periodo_id`,`clave`),
  CONSTRAINT `fk_hist_config_periodo` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insertar un periodo inicial por defecto para los datos existentes y asignar los alumnos a este periodo
INSERT INTO `periodos` (`nombre`, `fecha_inicio`, `fecha_fin`, `estado`) VALUES ('Periodo Inicial 2026', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 'ACTIVO');

-- Actualizar alumnos existentes al periodo 1
UPDATE `alumnos` SET `periodo_id` = 1 WHERE `periodo_id` IS NULL;
