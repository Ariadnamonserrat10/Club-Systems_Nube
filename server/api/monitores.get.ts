import type { RowDataPacket } from 'mysql2/promise'
import { requireUser } from '../utils/auth'
import { db } from '../utils/db'

export default defineEventHandler(async (event) => {
  await requireUser(event)
  const clubId = Number(getQuery(event).club_id || 0)
  if (!clubId) throw createError({ statusCode: 400, statusMessage: 'club_id es requerido' })
  const [rows] = await db().execute<RowDataPacket[]>(
    `SELECT u.id, u.nombre, u.apellidoP, u.apellidoM, u.usuario
       FROM usuarios u
       LEFT JOIN roles r ON r.id = u.rol_id
       LEFT JOIN usuario_club uc ON uc.usuario_id = u.id AND uc.activo = 1
      WHERE COALESCE(r.nombre, u.tipo) = 'MONITOR'
        AND (uc.club_id = ? OR u.club_asignado = ?)
      GROUP BY u.id ORDER BY u.nombre, u.apellidoP`,
    [clubId, clubId]
  )
  return { status: 'success', data: { club_id: clubId, monitores: rows } }
})
