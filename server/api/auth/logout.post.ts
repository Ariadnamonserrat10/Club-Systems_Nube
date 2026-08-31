import { bearerToken } from '../../utils/auth'
import { revokeToken } from '../../utils/tokens'

export default defineEventHandler(async (event) => {
  const token = bearerToken(event)
  if (token) await revokeToken(token, 'SESSION')
  return { status: 'success', message: 'Sesión cerrada' }
})
