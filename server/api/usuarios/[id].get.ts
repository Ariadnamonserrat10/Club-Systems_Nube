import type { RowDataPacket } from 'mysql2/promise'
import { requireUser } from '../../utils/auth'
import { db } from '../../utils/db'
import { positiveId } from '../../utils/validation'

export default defineEventHandler(async (event) => {
  const user = await requireUser(event)
  const id = positiveId(getRouterParam(event, 'id'))
  if (user.rol !== 'SUPERADMIN' && user.id !== id) throw createError({ statusCode: 403, statusMessage: 'Solo puedes consultar tu propia cuenta' })
  const [rows] = await db().execute<RowDataPacket[]>('SELECT id, nombre, apellidoP, apellidoM, usuario, tipo, numeroControl, telefono, carrera_id, semestre_id, club_asignado, foto FROM usuarios WHERE id = ? LIMIT 1', [id])
  if (!rows[0]) throw createError({ statusCode: 404, statusMessage: 'Usuario no encontrado' })
  return { status: 'success', data: rows[0] }
})
