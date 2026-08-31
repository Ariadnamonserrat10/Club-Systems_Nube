import { z } from 'zod'
import { bearerToken, requireUser } from '../utils/auth'
import { db } from '../utils/db'
import { audit } from '../utils/audit'

const schema = z.object({ session_id: z.coerce.number().int().positive().optional(), mode: z.enum(['single', 'others', 'all']).default('single') })
export default defineEventHandler(async event => {
  const user = await requireUser(event)
  const rawBody: unknown = await readBody(event).catch(() => ({}))
  const body: Record<string, unknown> = rawBody && typeof rawBody === 'object' && !Array.isArray(rawBody) ? rawBody as Record<string, unknown> : {}
  const query = getQuery(event) as Record<string, unknown>
  const parsed = schema.safeParse(Object.assign({}, query, body))
  if (!parsed.success || (parsed.data.mode === 'single' && !parsed.data.session_id)) throw createError({ statusCode: 422, statusMessage: 'Sesión inválida' })
  const selector = bearerToken(event)?.split('.', 1)[0] || ''
  let sql = "UPDATE tokens SET activo = 0 WHERE user_id = ? AND tipo = 'SESSION' AND activo = 1"
  const params: Array<string | number> = [user.id]
  if (parsed.data.mode === 'single') { sql += ' AND id = ?'; params.push(parsed.data.session_id!) }
  if (parsed.data.mode === 'others') { sql += ' AND selector <> ?'; params.push(selector) }
  const [result] = await db().execute(sql, params)
  const count = Number((result as { affectedRows: number }).affectedRows)
  if (parsed.data.mode === 'single' && !count) throw createError({ statusCode: 404, statusMessage: 'Sesión no encontrada' })
  await audit(user.id, 'SESIONES_REVOCADAS', `Modo ${parsed.data.mode}, cantidad ${count}`)
  return { status: 'success', message: `Se cerraron ${count} sesiones`, revoked_count: count, mode: parsed.data.mode }
})
