import { z } from 'zod'
import type { RowDataPacket } from 'mysql2/promise'
import { requireUser } from '../utils/auth'
import { transaction, db } from '../utils/db'
import { audit } from '../utils/audit'

const schema = z.object({ club_id: z.coerce.number().int().positive(), fecha: z.iso.date(), registros: z.array(z.object({ alumno_id: z.coerce.number().int().positive(), presente: z.coerce.boolean() })) })

export default defineEventHandler(async (event) => {
  const user = await requireUser(event, ['SUPERADMIN', 'ADMIN', 'MONITOR'])
  const parsed = schema.safeParse(await readBody(event))
  if (!parsed.success) throw createError({ statusCode: 422, statusMessage: 'Datos de asistencia inválidos' })
  const [periods] = await db().query<RowDataPacket[]>("SELECT id FROM periodos WHERE estado = 'ACTIVO' LIMIT 1")
  if (!periods.length) throw createError({ statusCode: 403, statusMessage: 'No se pueden registrar asistencias porque el periodo está cerrado' })
  if (user.rol === 'MONITOR' && Number(user.club_asignado) !== parsed.data.club_id) throw createError({ statusCode: 403, statusMessage: 'Este club no está asignado al monitor' })
  let saved = 0
  await transaction(async connection => {
    for (const item of parsed.data.registros) {
      const [result] = await connection.execute(
        `INSERT INTO asistencias (id_alumno, fecha, presente)
         SELECT id, ?, ? FROM alumnos WHERE id = ? AND id_club = ?
         ON DUPLICATE KEY UPDATE presente = VALUES(presente)`,
        [parsed.data.fecha, item.presente ? 1 : 0, item.alumno_id, parsed.data.club_id])
      saved += (result as { affectedRows: number }).affectedRows ? 1 : 0
    }
  })
  await audit(user.id, 'ASISTENCIAS_REGISTRADAS', `Club ${parsed.data.club_id}, fecha ${parsed.data.fecha}, registros ${saved}`)
  return { status: 'success', message: `Registros guardados: ${saved}` }
})
