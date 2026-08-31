import { z } from 'zod'
import { requireUser } from '../../utils/auth'
import { db } from '../../utils/db'
import { audit } from '../../utils/audit'

const schema = z.object({ clave: z.string().trim().min(1).max(50), valor: z.coerce.string().max(10000) })

export default defineEventHandler(async (event) => {
  const user = await requireUser(event, ['SUPERADMIN'])
  const parsed = schema.safeParse(await readBody(event))
  if (!parsed.success) throw createError({ statusCode: 422, statusMessage: 'Configuración inválida' })
  await db().execute(
    'INSERT INTO app_config (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)',
    [parsed.data.clave, parsed.data.valor]
  )
  await audit(user.id, 'CONFIG_ACTUALIZADA', `Clave: ${parsed.data.clave}`)
  return { status: 'success', ok: true, ...parsed.data }
})
