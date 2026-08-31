import { z } from 'zod'
import { requireUser } from '../../utils/auth'
import { transaction } from '../../utils/db'
import { audit } from '../../utils/audit'

const schema = z.object({ monitor_id: z.coerce.number().int().positive(), club_id: z.coerce.number().int().positive() })

export default defineEventHandler(async (event) => {
  const user = await requireUser(event, ['SUPERADMIN'])
  const parsed = schema.safeParse(await readBody(event))
  if (!parsed.success) throw createError({ statusCode: 422, statusMessage: 'Monitor y club son requeridos' })
  const { monitor_id, club_id } = parsed.data
  await transaction(async (connection) => {
    await connection.execute(
      `INSERT INTO usuario_club (usuario_id, club_id, fecha_asignacion, activo, asignado_por)
       VALUES (?, ?, CURDATE(), 1, ?)
       ON DUPLICATE KEY UPDATE activo = 1, fecha_asignacion = CURDATE(), asignado_por = VALUES(asignado_por)`,
      [monitor_id, club_id, user.id]
    )
    await connection.execute('UPDATE usuarios SET club_asignado = ? WHERE id = ?', [club_id, monitor_id])
  })
  await audit(user.id, 'MONITOR_ASIGNADO', `Monitor ${monitor_id}, club ${club_id}`)
  return { status: 'success', message: 'Monitor asignado correctamente', data: parsed.data }
})
