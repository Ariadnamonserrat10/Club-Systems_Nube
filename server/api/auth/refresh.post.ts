import { validateToken, issueToken, revokeToken } from '../../utils/tokens'

export default defineEventHandler(async (event) => {
  const body = await readBody<{ refresh_token?: string }>(event)
  const refreshValue = body?.refresh_token
  if (!refreshValue) throw createError({ statusCode: 401, statusMessage: 'Token de renovación requerido' })

  const current = await validateToken(refreshValue, 'REFRESH')
  if (!current) throw createError({ statusCode: 401, statusMessage: 'Token de renovación inválido' })

  await revokeToken(refreshValue, 'REFRESH')
  const access = await issueToken(current.user_id, 'SESSION', 2 * 60 * 60, event)
  const refresh = await issueToken(current.user_id, 'REFRESH', 7 * 24 * 60 * 60, event)
  return {
    status: 'success',
    token: access.value,
    remember_token: refresh.value,
    token_expires_at: access.expiresAt.toISOString()
  }
})
