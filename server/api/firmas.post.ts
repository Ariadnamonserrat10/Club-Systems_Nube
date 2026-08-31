import { z } from 'zod'
import type { RowDataPacket } from 'mysql2/promise'
import { requireUser } from '../utils/auth'
import { db } from '../utils/db'
import { audit } from '../utils/audit'

const schema = z.object({ cargo: z.enum(['jefe_actividades', 'jefe_promocion']), id_usuario: z.coerce.number().int().positive() })
export default defineEventHandler(async event => {
  const actor = await requireUser(event, ['SUPERADMIN'])
  const parsed = schema.safeParse(await readBody(event))
  if (!parsed.success) throw createError({ statusCode: 422, statusMessage: 'Cargo o usuario inválido' })
  const [users] = await db().execute<RowDataPacket[]>('SELECT id FROM usuarios WHERE id = ? AND COALESCE(activo, 1) = 1 LIMIT 1', [parsed.data.id_usuario])
  if (!users.length) throw createError({ statusCode: 404, statusMessage: 'Usuario no encontrado' })
  const key = `firma_${parsed.data.cargo}`
  await db().execute('INSERT INTO app_config (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)', [key, String(parsed.data.id_usuario)])
  await audit(actor.id, 'FIRMA_ASIGNADA', `${parsed.data.cargo}: usuario ${parsed.data.id_usuario}`)
  return { status: 'success', ok: true, ...parsed.data }
})
