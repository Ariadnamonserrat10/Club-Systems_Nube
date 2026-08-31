import { z } from 'zod'
import { requireUser } from '../../utils/auth'
import { db } from '../../utils/db'
import { audit } from '../../utils/audit'

const schema = z.object({ nombre: z.string().trim().min(1).max(120), descripcion: z.string().trim().max(500).nullish(), cantidad_total: z.coerce.number().int().min(0), club_id: z.coerce.number().int().positive().nullish() })
export default defineEventHandler(async event => {
  const user = await requireUser(event, ['SUPERADMIN', 'ADMIN'])
  const parsed = schema.safeParse(await readBody(event))
  if (!parsed.success) throw createError({ statusCode: 422, statusMessage: 'Datos del material inválidos' })
  const value = parsed.data
  const [result] = await db().execute('INSERT INTO materiales (nombre, descripcion, cantidad_total, cantidad_disponible, club_id, estado, creado_por) VALUES (?, ?, ?, ?, ?, ?, ?)', [value.nombre, value.descripcion || null, value.cantidad_total, value.cantidad_total, value.club_id || null, value.cantidad_total ? 'DISPONIBLE' : 'AGOTADO', user.id])
  const id = Number((result as { insertId: number }).insertId)
  await audit(user.id, 'MATERIAL_CREADO', `Material ${id}: ${value.nombre}, cantidad ${value.cantidad_total}`)
  setResponseStatus(event, 201)
  return { status: 'success', data: { id, ...value, cantidad_disponible: value.cantidad_total } }
})
