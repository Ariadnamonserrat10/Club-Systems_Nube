import { z } from 'zod'
import type { RowDataPacket } from 'mysql2/promise'
import { requireUser } from '../../../utils/auth'
import { transaction } from '../../../utils/db'
import { audit } from '../../../utils/audit'
import { positiveId } from '../../../utils/validation'

const schema = z.object({ tipo: z.enum(['ENTRADA', 'SALIDA', 'AJUSTE']), cantidad: z.coerce.number().int(), motivo: z.string().trim().min(1).max(255), documento_id: z.coerce.number().int().positive().nullish() }).superRefine((value, context) => { if (value.tipo !== 'AJUSTE' && value.cantidad <= 0) context.addIssue({ code: 'custom', message: 'La cantidad debe ser positiva' }); if (value.tipo === 'AJUSTE' && value.cantidad < 0) context.addIssue({ code: 'custom', message: 'El inventario no puede ser negativo' }) })
export default defineEventHandler(async event => {
  const user = await requireUser(event, ['SUPERADMIN', 'ADMIN'])
  const id = positiveId(getRouterParam(event, 'id'))
  const parsed = schema.safeParse(await readBody(event))
  if (!parsed.success) throw createError({ statusCode: 422, statusMessage: 'Movimiento inválido' })
  const value = parsed.data
  const available = await transaction(async connection => {
    const [rows] = await connection.execute<RowDataPacket[]>('SELECT cantidad_total, cantidad_disponible FROM materiales WHERE id = ? AND estado <> \'BAJA\' FOR UPDATE', [id])
    const material = rows[0]
    if (!material) throw createError({ statusCode: 404, statusMessage: 'Material no encontrado' })
    const current = Number(material.cantidad_disponible); const total = Number(material.cantidad_total)
    const next = value.tipo === 'ENTRADA' ? current + value.cantidad : value.tipo === 'SALIDA' ? current - value.cantidad : value.cantidad
    if (next < 0) throw createError({ statusCode: 409, statusMessage: 'No hay material suficiente' })
    const nextTotal = value.tipo === 'ENTRADA' ? total + value.cantidad : Math.max(total, next)
    await connection.execute('UPDATE materiales SET cantidad_total = ?, cantidad_disponible = ?, estado = ? WHERE id = ?', [nextTotal, next, next ? 'DISPONIBLE' : 'AGOTADO', id])
    await connection.execute('INSERT INTO movimientos_material (material_id, tipo, cantidad, motivo, documento_id, usuario_id) VALUES (?, ?, ?, ?, ?, ?)', [id, value.tipo, value.cantidad, value.motivo, value.documento_id || null, user.id])
    return next
  })
  await audit(user.id, 'MOVIMIENTO_MATERIAL', `Material ${id}, ${value.tipo}, cantidad ${value.cantidad}`)
  return { status: 'success', cantidad_disponible: available }
})
