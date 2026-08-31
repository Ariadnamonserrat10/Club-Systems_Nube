import { z } from 'zod'
import { requireUser } from '../../utils/auth'
import { db } from '../../utils/db'
import { audit } from '../../utils/audit'
import { positiveId, textPattern, titleCase } from '../../utils/validation'

const schema = z.object({
  nombre: z.string().trim().min(1).max(50).optional(), apellidoP: z.string().trim().min(1).max(50).optional(), apellidoM: z.string().trim().min(1).max(50).optional(),
  numeroControl: z.coerce.string().regex(/^\d{8}$/).optional(), telefono: z.coerce.string().regex(/^\d{7,15}$/).optional(),
  carrera_id: z.coerce.number().int().positive().optional(), semestre_id: z.coerce.number().int().positive().optional(), id_club: z.coerce.number().int().positive().optional()
}).refine(value => Object.keys(value).length > 0)

export default defineEventHandler(async (event) => {
  const user = await requireUser(event, ['SUPERADMIN', 'ADMIN', 'MONITOR'])
  const id = positiveId(getRouterParam(event, 'id'))
  const parsed = schema.safeParse(await readBody(event))
  if (!parsed.success) throw createError({ statusCode: 422, statusMessage: 'Datos del alumno inválidos' })
  if (user.rol === 'MONITOR' && parsed.data.id_club && Number(user.club_asignado) !== parsed.data.id_club) throw createError({ statusCode: 403, statusMessage: 'Este club no está asignado al monitor' })
  const fields: string[] = []; const values: Array<string | number> = []
  for (const [key, raw] of Object.entries(parsed.data)) {
    let value = raw as string | number
    if (['nombre', 'apellidoP', 'apellidoM'].includes(key)) { value = titleCase(String(raw)); if (!textPattern.test(String(value))) throw createError({ statusCode: 422, statusMessage: `${key} inválido` }) }
    fields.push(`${key} = ?`); values.push(value)
  }
  const monitorFilter = user.rol === 'MONITOR' ? ' AND id_club = ?' : ''
  const params = user.rol === 'MONITOR' ? [...values, id, Number(user.club_asignado)] : [...values, id]
  const [result] = await db().execute(`UPDATE alumnos SET ${fields.join(', ')} WHERE id = ?${monitorFilter}`, params)
  if (!(result as { affectedRows: number }).affectedRows) throw createError({ statusCode: 404, statusMessage: 'Alumno no encontrado' })
  await audit(user.id, 'ALUMNO_ACTUALIZADO', `Alumno ${id}`)
  return { status: 'success' }
})
