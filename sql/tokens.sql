-- =====================================================
-- TABLA TOKENS - Sistema de Autenticación Segura
-- =====================================================
-- Esta tabla es para:
--   - Sesiones seguras
--   - Tokens de "Recordarme"
--   - Tokens CSRF
--   - Tokens de restablecimiento de contraseña
--   - Prevención de ataques de repetición (replay attacks)
--   - Expiración de tokens
--   - Validación de dispositivo/sesión
--   - Invalidación en logout
--   - Gestión de múltiples sesiones
-- =====================================================

-- =====================================================
-- ESTRUCTURA COMPLETA DE LA TABLA (si no existe)
-- =====================================================

CREATE TABLE IF NOT EXISTS tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(128) NOT NULL COMMENT 'Token hasheado con SHA-256',
    selector VARCHAR(32) NOT NULL COMMENT 'Selector público para buscar el token sin revelar el hash',
    user_id INT NOT NULL COMMENT 'ID del usuario',
    tipo VARCHAR(20) NOT NULL COMMENT 'Tipo: SESSION, REMEMBER, CSRF, REFRESH, RESET, VERIFY',
    expires_at DATETIME NOT NULL COMMENT 'Fecha de expiración',
    activo TINYINT(1) DEFAULT 1 COMMENT '1 = activo, 0 = revocado/invalidado',
    ip VARCHAR(45) NULL COMMENT 'IP del cliente (IPv4 o IPv6)',
    user_agent VARCHAR(512) NULL COMMENT 'User-Agent del navegador',
    fingerprint VARCHAR(64) NULL COMMENT 'Hash de fingerprint del dispositivo (SHA-256)',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_token (token),
    INDEX idx_selector (selector),
    INDEX idx_user_id (user_id),
    INDEX idx_tipo (tipo),
    INDEX idx_expires_at (expires_at),
    INDEX idx_user_tipo (user_id, tipo, activo),
    
    CONSTRAINT fk_tokens_usuario 
        FOREIGN KEY (user_id) 
        REFERENCES usuarios(id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- SI LA TABLA YA EXISTE, AÑADIR COLUMNAS FALTANTES
-- =====================================================

-- Si la tabla no tiene columna 'selector':
-- ALTER TABLE tokens ADD COLUMN selector VARCHAR(32) NULL AFTER token;
-- ALTER TABLE tokens ADD INDEX idx_selector (selector);

-- Si la tabla no tiene columna 'ip':
-- ALTER TABLE tokens ADD COLUMN ip VARCHAR(45) NULL AFTER activo;

-- Si la tabla no tiene columna 'user_agent':
-- ALTER TABLE tokens ADD COLUMN user_agent VARCHAR(512) NULL AFTER ip;

-- Si la tabla no tiene columna 'fingerprint':
-- ALTER TABLE tokens ADD COLUMN fingerprint VARCHAR(64) NULL AFTER user_agent;

-- =====================================================
-- TIPOS DE TOKENS
-- =====================================================
-- SESSION:   Token de sesión principal (2 horas de vida)
-- REMEMBER:  Token de "Recordarme" (2 semanas)
-- CSRF:      Token CSRF para protección en formularios (1 hora)
-- REFRESH:   Token de renovación (1 semana)
-- RESET:     Token de restablecimiento de contraseña (30 minutos)
-- VERIFY:    Token de verificación de correo (24 horas)

-- =====================================================
-- EJEMPLOS DE CONSULTAS
-- =====================================================

-- Obtener todas las sesiones activas de un usuario
SET @user_id = 1;
SELECT 
    id, 
    user_id, 
    tipo, 
    expires_at, 
    activo, 
    ip, 
    creado_en,
    TIMESTAMPDIFF(HOUR, NOW(), expires_at) AS horas_restantes
FROM tokens 
WHERE user_id = @user_id 
  AND tipo = 'SESSION' 
  AND activo = 1 
  AND expires_at > NOW()
ORDER BY creado_en DESC;

-- Revocar todas las sesiones de un usuario (excepto la actual)
SET @user_id = 1;
SET @current_token_id = 123;
UPDATE tokens 
SET activo = 0 
WHERE user_id = @user_id 
  AND tipo = 'SESSION' 
  AND activo = 1
  AND id != @current_token_id;

-- Limpieza de tokens expirados o revocados (ejecutar periódicamente)
DELETE FROM tokens 
WHERE expires_at < NOW() 
   OR activo = 0;

-- Obtener conteo de sesiones activas por usuario
SELECT 
    u.id,
    CONCAT(u.nombre, ' ', u.apellidoP) AS usuario,
    u.tipo,
    COUNT(t.id) AS sesiones_activas
FROM usuarios u
LEFT JOIN tokens t ON u.id = t.user_id 
    AND t.tipo = 'SESSION' 
    AND t.activo = 1 
    AND t.expires_at > NOW()
GROUP BY u.id, u.nombre, u.apellidoP, u.tipo;

-- =====================================================
-- CONFIGURACIÓN DE LIMPIEZA AUTOMÁTICA (Event Scheduler)
-- =====================================================

-- Habilitar Event Scheduler si no está habilitado
-- SET GLOBAL event_scheduler = ON;

-- Crear evento para limpiar tokens cada hora
DELIMITER //
CREATE EVENT IF NOT EXISTS limpiar_tokens_expirados
ON SCHEDULE EVERY 1 HOUR
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    DELETE FROM tokens WHERE expires_at < NOW() OR activo = 0;
END //
DELIMITER ;

-- Verificar eventos creados
SHOW EVENTS;

-- =====================================================
-- CONSULTAS DE AUDITORÍA Y SEGURIDAD
-- =====================================================

-- Tokens creados en la última hora
SELECT 
    t.id,
    CONCAT(u.nombre, ' ', u.apellidoP) AS usuario,
    u.tipo,
    t.tipo AS token_tipo,
    t.ip,
    t.creado_en
FROM tokens t
JOIN usuarios u ON t.user_id = u.id
WHERE t.creado_en > DATE_SUB(NOW(), INTERVAL 1 HOUR)
ORDER BY t.creado_en DESC;

-- Sesiones desde IPs diferentes para un mismo usuario
SELECT 
    user_id,
    COUNT(DISTINCT ip) AS ips_distintas,
    GROUP_CONCAT(DISTINCT ip) AS ips
FROM tokens
WHERE tipo = 'SESSION'
  AND activo = 1
  AND expires_at > NOW()
GROUP BY user_id
HAVING ips_distintas > 1;
