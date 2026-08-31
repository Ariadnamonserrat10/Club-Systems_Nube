import { z } from 'zod'
import { requireUser } from '../../utils/auth'
import { db } from '../../utils/db'
import { audit } from '../../utils/audit'
import { positiveId } from '../../utils/validation'

const schema = z.object({ nombre: z.string().trim().min(1).max(50), fecha_inicio: z.iso.date(), fecha_fin: z.iso.date(), ya_editado: z.coerce.number().int().min(0).max(1).default(0) }).refine(v => v.fecha_fin >= v.fecha_inicio)

export default defineEventHandler(async (event) => {
  const user = await requireUser(event, ['SUPERADMIN', 'ADMIN'])
  const id = positiveId(getRouterParam(event, 'id'))
  const parsed = schema.safeParse(await readBody(event))
  if (!parsed.success) throw createError({ statusCode: 422, statusMessage: 'Datos del periodo inválidos' })
  const value = parsed.data
  const [result] = await db().execute('UPDATE periodos SET nombre = ?, fecha_inicio = ?, fecha_fin = ?, ya_editado = ? WHERE id = ?', [value.nombre, value.fecha_inicio, value.fecha_fin, value.ya_editado, id])
  if (!(result as { affectedRows: number }).affectedRows) throw createError({ statusCode: 404, statusMessage: 'Periodo no encontrado' })
  await audit(user.id, 'PERIODO_ACTUALIZADO', `Periodo ${id}`)
  return { status: 'success', message: 'Periodo actualizado' }
})
