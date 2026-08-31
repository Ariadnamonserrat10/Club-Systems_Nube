import type { RowDataPacket } from 'mysql2/promise'
import type { H3Event } from 'h3'
import { db } from './db'
import { validateToken } from './tokens'
import type { AuthUser, UserRole } from '../types/auth'

interface UserRow extends RowDataPacket, AuthUser {}

export function bearerToken(event: H3Event) {
  const authorization = getHeader(event, 'authorization') || ''
  const match = authorization.match(/^Bearer\s+(.+)$/i)
  return match?.[1] || null
}

export async function requireUser(event: H3Event, allowedRoles?: UserRole[]) {
  const value = bearerToken(event)
  if (!value) throw createError({ statusCode: 401, statusMessage: 'No autenticado' })

  const token = await validateToken(value, 'SESSION')
  if (!token) throw createError({ statusCode: 401, statusMessage: 'Sesión inválida o expirada' })

  const [rows] = await db().execute<UserRow[]>(
    `SELECT u.id, u.usuario, u.nombre, u.apellidoP, u.apellidoM,
            u.tipo, COALESCE(r.nombre, u.tipo) AS rol, u.club_asignado
       FROM usuarios u
       LEFT JOIN roles r ON r.id = u.rol_id
      WHERE u.id = ? AND COALESCE(u.activo, 1) = 1
      LIMIT 1`,
    [token.user_id]
  )
  const user = rows[0]
  if (!user) throw createError({ statusCode: 401, statusMessage: 'Usuario inactivo o inexistente' })

  const normalizedRole = (user.rol === 'OFICINA' ? 'ADMIN' : user.rol) as UserRole
  user.rol = normalizedRole
  if (allowedRoles?.length && !allowedRoles.includes(normalizedRole)) {
    throw createError({ statusCode: 403, statusMessage: 'No tienes permiso para realizar esta acción' })
  }
  event.context.user = user
  return user
}

declare module 'h3' {
  interface H3EventContext {
    user?: AuthUser
  }
}
