import type { RowDataPacket } from 'mysql2/promise'
import { requireUser } from '../../../utils/auth'
import { db } from '../../../utils/db'
import { positiveId } from '../../../utils/validation'

export default defineEventHandler(async event => {
  const user = await requireUser(event)
  const id = positiveId(getRouterParam(event, 'id'))
  if (user.rol === 'MONITOR') {
    const [allowed] = await db().execute<RowDataPacket[]>('SELECT id FROM alumnos WHERE id = ? AND id_club = ? LIMIT 1', [id, user.club_asignado])
    if (!allowed.length) throw createError({ statusCode: 403, statusMessage: 'El alumno no pertenece al club asignado' })
  }
  const [rows] = await db().execute<RowDataPacket[]>('SELECT alumno_id, alergias, restricciones_fisicas, condicion_emergencia, contacto_emergencia, telefono_emergencia, observaciones, actualizado_en FROM datos_medicos_basicos WHERE alumno_id = ? LIMIT 1', [id])
  return { status: 'success', data: rows[0] || null }
})
