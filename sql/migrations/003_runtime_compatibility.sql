ALTER TABLE periodos ADD COLUMN IF NOT EXISTS ya_editado TINYINT(1) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS asistencias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_alumno INT NOT NULL,
  fecha DATE NOT NULL,
  presente TINYINT(1) NOT NULL DEFAULT 0,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_alumno_fecha (id_alumno, fecha),
  INDEX idx_fecha (fecha),
  INDEX idx_alumno (id_alumno),
  CONSTRAINT fk_asistencia_alumno FOREIGN KEY (id_alumno) REFERENCES alumnos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tokens (
  id INT AUTO_INCREMENT PRIMARY KEY,
  token VARCHAR(128) NOT NULL,
  selector VARCHAR(32) NOT NULL,
  user_id INT NOT NULL,
  tipo VARCHAR(20) NOT NULL,
  expires_at DATETIME NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(512) NULL,
  fingerprint VARCHAR(64) NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_token (token), INDEX idx_selector (selector), INDEX idx_user_tipo (user_id, tipo, activo),
  CONSTRAINT fk_token_usuario FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
