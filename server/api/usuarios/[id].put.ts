import bcrypt from 'bcryptjs'
import { z } from 'zod'
import { requireUser } from '../../utils/auth'
import { db, transaction } from '../../utils/db'
import { audit } from '../../utils/audit'
import { positiveId, textPattern, titleCase } from '../../utils/validation'

const passwordRule = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8}$/
const schema = z.object({
  nombre: z.string().trim().min(1).max(50).optional(), apellidoP: z.string().trim().min(1).max(50).optional(), apellidoM: z.string().trim().max(50).optional(),
  usuario: z.string().regex(/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8}$/).optional(), password: z.string().regex(passwordRule).optional(),
  tipo: z.enum(['OFICINA', 'MONITOR']).optional(), numeroControl: z.union([z.string().regex(/^\d{8}$/), z.null(), z.literal('')]).optional(),
  telefono: z.union([z.string().regex(/^\d{7,15}$/), z.null(), z.literal('')]).optional(), carrera_id: z.coerce.number().int().positive().nullish(),
  semestre_id: z.coerce.number().int().positive().nullish(), club_asignado: z.coerce.number().int().positive().nullish(), foto: z.string().max(255).nullish(),
  activo: z.coerce.number().int().min(0).max(1).optional()
}).refine(value => Object.keys(value).length > 0)

export default defineEventHandler(async (event) => {
  const actor = await requireUser(event, ['SUPERADMIN'])
  const id = positiveId(getRouterParam(event, 'id'))
  const parsed = schema.safeParse(await readBody(event))
  if (!parsed.success) throw createError({ statusCode: 422, statusMessage: 'Datos del usuario inválidos' })
  const fields: string[] = []; const values: Array<string | number | null> = []
  for (const [key, raw] of Object.entries(parsed.data)) {
    let value = raw as string | number | null
    if (['nombre', 'apellidoP', 'apellidoM'].includes(key)) { value = titleCase(String(raw)); if (value && !textPattern.test(String(value))) throw createError({ statusCode: 422, statusMessage: `${key} inválido` }) }
    if (key === 'password') value = await bcrypt.hash(String(raw), 12)
    if (value === '') value = null
    fields.push(`${key} = ?`); values.push(value)
  }
  if (parsed.data.tipo) { fields.push('rol_id = (SELECT id FROM roles WHERE nombre = ? LIMIT 1)'); values.push(parsed.data.tipo === 'OFICINA' ? 'ADMIN' : 'MONITOR') }
  try {
    await transaction(async connection => {
      const [result] = await connection.execute(`UPDATE usuarios SET ${fields.join(', ')} WHERE id = ?`, [...values, id])
      if (!(result as { affectedRows: number }).affectedRows) throw createError({ statusCode: 404, statusMessage: 'Usuario no encontrado' })
      if ('club_asignado' in parsed.data) {
        await connection.execute('UPDATE usuario_club SET activo = 0 WHERE usuario_id = ?', [id])
        if (parsed.data.club_asignado) await connection.execute(
          `INSERT INTO usuario_club (usuario_id, club_id, fecha_asignacion, activo, asignado_por) VALUES (?, ?, CURDATE(), 1, ?)
           ON DUPLICATE KEY UPDATE fecha_asignacion = CURDATE(), activo = 1, asignado_por = VALUES(asignado_por)`, [id, parsed.data.club_asignado, actor.id])
      }
    })
  } catch (error: any) {
    if (error?.code === 'ER_DUP_ENTRY') throw createError({ statusCode: 409, statusMessage: 'Usuario o número de control duplicado' })
    throw error
  }
  await audit(actor.id, 'USUARIO_ACTUALIZADO', `Usuario ${id}`)
  return { status: 'success' }
})
