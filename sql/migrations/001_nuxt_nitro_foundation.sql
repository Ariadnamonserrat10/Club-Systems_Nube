-- Base de seguridad requerida por la migración a Nuxt/Nitro.
CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(30) NOT NULL UNIQUE,
  descripcion VARCHAR(255) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS permisos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  clave VARCHAR(80) NOT NULL UNIQUE,
  nombre VARCHAR(120) NOT NULL,
  descripcion VARCHAR(255) NULL,
  modulo VARCHAR(60) NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rol_permiso (
  rol_id INT NOT NULL,
  permiso_id INT NOT NULL,
  concedido_por INT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (rol_id, permiso_id),
  CONSTRAINT fk_rol_permiso_rol FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_rol_permiso_permiso FOREIGN KEY (permiso_id) REFERENCES permisos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO roles (nombre, descripcion) VALUES
  ('SUPERADMIN', 'Administra seguridad, cuentas, roles y configuración'),
  ('ADMIN', 'Gestiona la operación del Departamento de Actividades Extraescolares'),
  ('MONITOR', 'Gestiona únicamente los clubes y alumnos asignados')
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS rol_id INT NULL AFTER id;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS activo TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS cambio_password_requerido TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS ultimo_acceso DATETIME NULL;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS creado_por INT NULL;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS actualizado_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

UPDATE usuarios u
JOIN roles r ON r.nombre = CASE WHEN u.tipo = 'MONITOR' THEN 'MONITOR' ELSE 'ADMIN' END
SET u.rol_id = r.id
WHERE u.rol_id IS NULL;

CREATE TABLE IF NOT EXISTS usuario_club (
  usuario_id INT NOT NULL,
  club_id INT NOT NULL,
  fecha_asignacion DATE NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  asignado_por INT NULL,
  PRIMARY KEY (usuario_id, club_id),
  CONSTRAINT fk_usuario_club_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_usuario_club_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_usuario_club_asignador FOREIGN KEY (asignado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
