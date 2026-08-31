import { z } from 'zod'
import { requireUser } from '../../utils/auth'
import { db } from '../../utils/db'
import { audit } from '../../utils/audit'
import { positiveId, textPattern, titleCase } from '../../utils/validation'

const schema = z.object({
  nombre: z.string().trim().min(1).max(100).optional(), tipo: z.enum(['CULTURAL', 'DEPORTIVO']).optional(),
  descripcion: z.string().trim().min(1).max(255).optional(), cupo_limite: z.coerce.number().int().min(1).max(50).optional(),
  id_responsable: z.union([z.coerce.number().int().positive(), z.null(), z.literal('')]).optional()
}).refine(value => Object.keys(value).length > 0)

export default defineEventHandler(async (event) => {
  const user = await requireUser(event, ['SUPERADMIN'])
  const id = positiveId(getRouterParam(event, 'id'))
  const parsed = schema.safeParse(await readBody(event))
  if (!parsed.success) throw createError({ statusCode: 422, statusMessage: 'Datos del club inválidos' })
  const fields: string[] = []; const values: Array<string | number | null> = []
  for (const [key, raw] of Object.entries(parsed.data)) {
    let value: string | number | null = raw as string | number | null
    if (key === 'nombre' || key === 'descripcion') {
      value = titleCase(String(raw)); if (!textPattern.test(String(value))) throw createError({ statusCode: 422, statusMessage: `${key} solo debe contener texto` })
    }
    if (key === 'id_responsable' && value === '') value = null
    fields.push(`${key} = ?`); values.push(value)
  }
  const [result] = await db().execute(`UPDATE clubs SET ${fields.join(', ')} WHERE id = ?`, [...values, id])
  if (!(result as { affectedRows: number }).affectedRows) throw createError({ statusCode: 404, statusMessage: 'Club no encontrado' })
  await audit(user.id, 'CLUB_ACTUALIZADO', `Club ${id}`)
  return { status: 'success', data: { id, ...parsed.data } }
})
