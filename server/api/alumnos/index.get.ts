import type { RowDataPacket } from 'mysql2/promise'
import { requireUser } from '../../utils/auth'
import { db } from '../../utils/db'

export default defineEventHandler(async (event) => {
  const user = await requireUser(event)
  const requestedClub = Number(getQuery(event).club_id || 0)
  const clubId = user.rol === 'MONITOR' ? Number(user.club_asignado || 0) : requestedClub
  const params: number[] = []
  let filter = ''
  if (clubId > 0) { filter = ' AND a.id_club = ?'; params.push(clubId) }
  const [rows] = await db().execute<RowDataPacket[]>(
    `SELECT a.id, a.nombre, a.apellidoP, a.apellidoM, a.numeroControl, a.telefono,
            a.carrera_id, a.semestre_id, a.id_club, a.periodo_id, a.estado_periodo, a.fecha_registro
       FROM alumnos a JOIN periodos p ON p.id = a.periodo_id
      WHERE p.estado = 'ACTIVO'${filter}
      ORDER BY a.apellidoP, a.apellidoM, a.nombre`, params)
  return { status: 'success', data: rows }
})
