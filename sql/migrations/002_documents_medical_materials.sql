-- Módulos documentales y datos mínimos para emergencias.
CREATE TABLE IF NOT EXISTS datos_medicos_basicos (
  alumno_id INT PRIMARY KEY,
  alergias VARCHAR(255) NULL,
  restricciones_fisicas VARCHAR(255) NULL,
  condicion_emergencia VARCHAR(255) NULL,
  contacto_emergencia VARCHAR(120) NOT NULL,
  telefono_emergencia VARCHAR(15) NOT NULL,
  observaciones VARCHAR(500) NULL,
  actualizado_por INT NOT NULL,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_medico_alumno FOREIGN KEY (alumno_id) REFERENCES alumnos(id) ON DELETE CASCADE,
  CONSTRAINT fk_medico_usuario FOREIGN KEY (actualizado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS documentos (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  folio VARCHAR(40) NOT NULL UNIQUE,
  tipo ENUM('PAGO', 'REPOSICION_CONSTANCIA', 'MATERIAL', 'OTRO') NOT NULL,
  alumno_id INT NULL,
  club_id INT NULL,
  periodo_id INT NULL,
  nombre_original VARCHAR(255) NOT NULL,
  nombre_archivo VARCHAR(80) NOT NULL UNIQUE,
  mime_type VARCHAR(80) NOT NULL,
  tamano_bytes INT UNSIGNED NOT NULL,
  hash_sha256 CHAR(64) NOT NULL,
  concepto VARCHAR(255) NULL,
  monto DECIMAL(10,2) NULL,
  estado ENUM('ACTIVO', 'ANULADO') NOT NULL DEFAULT 'ACTIVO',
  creado_por INT NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_documento_alumno FOREIGN KEY (alumno_id) REFERENCES alumnos(id) ON DELETE SET NULL,
  CONSTRAINT fk_documento_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE SET NULL,
  CONSTRAINT fk_documento_periodo FOREIGN KEY (periodo_id) REFERENCES periodos(id) ON DELETE SET NULL,
  CONSTRAINT fk_documento_usuario FOREIGN KEY (creado_por) REFERENCES usuarios(id),
  INDEX idx_documento_tipo (tipo), INDEX idx_documento_alumno (alumno_id), INDEX idx_documento_fecha (creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS materiales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  descripcion VARCHAR(500) NULL,
  cantidad_total INT UNSIGNED NOT NULL DEFAULT 0,
  cantidad_disponible INT UNSIGNED NOT NULL DEFAULT 0,
  club_id INT NULL,
  estado ENUM('DISPONIBLE', 'AGOTADO', 'BAJA') NOT NULL DEFAULT 'DISPONIBLE',
  creado_por INT NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_material_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE SET NULL,
  CONSTRAINT fk_material_usuario FOREIGN KEY (creado_por) REFERENCES usuarios(id),
  CHECK (cantidad_disponible <= cantidad_total)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS movimientos_material (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  material_id INT NOT NULL,
  tipo ENUM('ENTRADA', 'SALIDA', 'AJUSTE') NOT NULL,
  cantidad INT NOT NULL,
  motivo VARCHAR(255) NOT NULL,
  documento_id BIGINT NULL,
  usuario_id INT NOT NULL,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_movimiento_material FOREIGN KEY (material_id) REFERENCES materiales(id),
  CONSTRAINT fk_movimiento_documento FOREIGN KEY (documento_id) REFERENCES documentos(id) ON DELETE SET NULL,
  CONSTRAINT fk_movimiento_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  INDEX idx_movimiento_material (material_id, creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
