import type { RowDataPacket } from 'mysql2/promise'
import { requireUser } from '../../utils/auth'
import { db } from '../../utils/db'

export default defineEventHandler(async (event) => {
  await requireUser(event)
  const club = String(getQuery(event).club_name || '').trim()
  if (!club) throw createError({ statusCode: 400, statusMessage: 'Nombre del club requerido' })
  const [rows] = await db().execute<RowDataPacket[]>('SELECT nombre_estudiante, nivel_desempeno, valor_numerico, observaciones FROM evaluaciones WHERE nombre_club = ? ORDER BY fecha_registro DESC', [club])
  return { status: 'success', data: rows }
})
