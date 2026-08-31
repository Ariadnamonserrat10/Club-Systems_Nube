INSERT INTO permisos (clave, nombre, descripcion, modulo) VALUES
  ('usuarios.ver', 'Consultar usuarios', 'Consultar el directorio de cuentas', 'usuarios'),
  ('usuarios.crear', 'Crear usuarios', 'Crear cuentas y entregar credenciales', 'usuarios'),
  ('usuarios.editar', 'Editar usuarios', 'Editar cuentas, roles y asignaciones', 'usuarios'),
  ('usuarios.eliminar', 'Desactivar usuarios', 'Desactivar cuentas sin borrar auditoría', 'usuarios'),
  ('configuracion.editar', 'Editar configuración', 'Modificar configuración y firmas institucionales', 'configuracion'),
  ('clubs.gestionar', 'Gestionar clubes', 'Crear, editar y eliminar clubes', 'clubs'),
  ('alumnos.gestionar', 'Gestionar alumnos', 'Registrar, editar y reinscribir alumnos', 'alumnos'),
  ('periodos.gestionar', 'Gestionar periodos', 'Crear, editar y cerrar periodos', 'periodos'),
  ('documentos.gestionar', 'Gestionar documentos', 'Registrar folios y comprobantes', 'documentos'),
  ('materiales.gestionar', 'Gestionar materiales', 'Administrar inventario y movimientos', 'materiales'),
  ('asistencias.club', 'Registrar asistencias', 'Registrar asistencias del club asignado', 'asistencias'),
  ('evaluaciones.club', 'Registrar evaluaciones', 'Evaluar alumnos del club asignado', 'evaluaciones')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion), modulo = VALUES(modulo);

DELETE rp FROM rol_permiso rp JOIN roles r ON r.id = rp.rol_id WHERE r.nombre IN ('SUPERADMIN', 'ADMIN', 'MONITOR');

INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permisos p WHERE r.nombre = 'SUPERADMIN';

INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p ON p.clave IN (
  'clubs.gestionar', 'alumnos.gestionar', 'periodos.gestionar', 'documentos.gestionar', 'materiales.gestionar'
) WHERE r.nombre = 'ADMIN';

INSERT INTO rol_permiso (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p ON p.clave IN ('asistencias.club', 'evaluaciones.club')
WHERE r.nombre = 'MONITOR';
