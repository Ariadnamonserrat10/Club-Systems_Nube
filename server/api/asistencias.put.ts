import { z } from 'zod'
import type { RowDataPacket } from 'mysql2/promise'
import { requireUser } from '../utils/auth'
import { db } from '../utils/db'
import { audit } from '../utils/audit'

const schema = z.object({ alumno_id: z.coerce.number().int().positive(), fecha: z.iso.date(), presente: z.coerce.boolean() })

export default defineEventHandler(async (event) => {
  const user = await requireUser(event, ['SUPERADMIN', 'ADMIN', 'MONITOR'])
  const parsed = schema.safeParse(await readBody(event))
  if (!parsed.success) throw createError({ statusCode: 422, statusMessage: 'Datos de asistencia inválidos' })
  const [periods] = await db().query<RowDataPacket[]>("SELECT id FROM periodos WHERE estado = 'ACTIVO' LIMIT 1")
  if (!periods.length) throw createError({ statusCode: 403, statusMessage: 'El periodo está cerrado' })
  if (user.rol === 'MONITOR') {
    const [students] = await db().execute<RowDataPacket[]>('SELECT id FROM alumnos WHERE id = ? AND id_club = ? LIMIT 1', [parsed.data.alumno_id, user.club_asignado])
    if (!students.length) throw createError({ statusCode: 403, statusMessage: 'El alumno no pertenece al club asignado' })
  }
  await db().execute('INSERT INTO asistencias (id_alumno, fecha, presente) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE presente = VALUES(presente)', [parsed.data.alumno_id, parsed.data.fecha, parsed.data.presente ? 1 : 0])
  await audit(user.id, 'ASISTENCIA_ACTUALIZADA', `Alumno ${parsed.data.alumno_id}, fecha ${parsed.data.fecha}`)
  return { status: 'success' }
})
