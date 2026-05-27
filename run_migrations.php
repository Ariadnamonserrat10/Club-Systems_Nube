<?php
include __DIR__ . "/Backend/db.php";

$queries = [
    "CREATE TABLE IF NOT EXISTS `periodos` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `nombre` varchar(50) NOT NULL,
      `fecha_inicio` date NOT NULL,
      `fecha_fin` date NOT NULL,
      `estado` enum('ACTIVO','CERRADO') NOT NULL DEFAULT 'ACTIVO',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
    
    "ALTER TABLE `alumnos` DROP INDEX `numeroControl`",
    
    "ALTER TABLE `alumnos` ADD COLUMN `periodo_id` int(11) DEFAULT NULL",
    
    "ALTER TABLE `alumnos` ADD COLUMN `estado_periodo` enum('ACTIVO','ACREDITADO','REPROBADO') NOT NULL DEFAULT 'ACTIVO'",
    
    "ALTER TABLE `alumnos` ADD UNIQUE KEY `uniq_alumno_periodo` (`numeroControl`, `periodo_id`)",
    
    "ALTER TABLE `alumnos` ADD CONSTRAINT `fk_alumnos_periodo` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`)",
    
    "CREATE TABLE IF NOT EXISTS `historial_configuracion` (
      `periodo_id` int(11) NOT NULL,
      `clave` varchar(50) NOT NULL,
      `valor` text DEFAULT NULL,
      PRIMARY KEY (`periodo_id`,`clave`),
      CONSTRAINT `fk_hist_config_periodo` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
    
    "INSERT INTO `periodos` (`nombre`, `fecha_inicio`, `fecha_fin`, `estado`) SELECT 'Periodo Inicial 2026', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 MONTH), 'ACTIVO' FROM DUAL WHERE NOT EXISTS (SELECT id FROM periodos LIMIT 1)",
    
    "UPDATE `alumnos` SET `periodo_id` = (SELECT id FROM periodos ORDER BY id LIMIT 1) WHERE `periodo_id` IS NULL"
];

foreach ($queries as $q) {
    echo "Running: " . substr($q, 0, 50) . "...\n";
    try {
        if (!$conexion->query($q)) {
            echo "Error: " . $conexion->error . "\n";
        } else {
            echo "Success\n";
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
}
?>
