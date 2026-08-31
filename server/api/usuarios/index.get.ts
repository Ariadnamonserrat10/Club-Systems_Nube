import type { RowDataPacket } from 'mysql2/promise'
import { requireUser } from '../../utils/auth'
import { db } from '../../utils/db'

export default defineEventHandler(async (event) => {
  await requireUser(event, ['SUPERADMIN'])
  const [rows] = await db().query<RowDataPacket[]>(
    `SELECT u.id, u.nombre, u.apellidoP, u.apellidoM, u.usuario, u.tipo, COALESCE(r.nombre, u.tipo) rol,
            u.numeroControl, u.telefono, u.carrera_id, u.semestre_id, u.club_asignado, u.foto, COALESCE(u.activo, 1) activo
       FROM usuarios u LEFT JOIN roles r ON r.id = u.rol_id
      ORDER BY u.tipo, u.apellidoP, u.apellidoM, u.nombre`)
  return { status: 'success', data: rows }
})
