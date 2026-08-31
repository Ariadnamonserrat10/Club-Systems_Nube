import bcrypt from 'bcryptjs'
import { z } from 'zod'
import type { RowDataPacket } from 'mysql2/promise'
import { db } from '../../utils/db'
import { issueToken } from '../../utils/tokens'

interface LoginUser extends RowDataPacket {
  id: number
  usuario: string
  password: string
  nombre: string
  apellidoP: string
  apellidoM: string
  tipo: string
  rol: string | null
  club_asignado: number | null
}

const schema = z.object({
  usuario: z.string().trim().min(1).max(80),
  password: z.string().min(1).max(200)
})

export default defineEventHandler(async (event) => {
  const parsed = schema.safeParse(await readBody(event))
  if (!parsed.success) {
    throw createError({ statusCode: 422, statusMessage: 'Usuario y contraseña son obligatorios' })
  }

  const [rows] = await db().execute<LoginUser[]>(
    `SELECT u.id, u.usuario, u.password, u.nombre, u.apellidoP, u.apellidoM,
            u.tipo, r.nombre AS rol, u.club_asignado
       FROM usuarios u
       LEFT JOIN roles r ON r.id = u.rol_id
      WHERE u.usuario = ? AND COALESCE(u.activo, 1) = 1
      LIMIT 1`,
    [parsed.data.usuario]
  )
  const user = rows[0]
  const compatibleHash = user?.password?.startsWith('$2y$')
    ? `$2b$${user.password.slice(4)}`
    : user?.password
  if (!user || !compatibleHash || !(await bcrypt.compare(parsed.data.password, compatibleHash))) {
    throw createError({ statusCode: 401, statusMessage: 'Usuario o contraseña incorrectos' })
  }

  const access = await issueToken(user.id, 'SESSION', 2 * 60 * 60, event)
  const refresh = await issueToken(user.id, 'REFRESH', 7 * 24 * 60 * 60, event)
  const role = user.rol || (user.tipo === 'OFICINA' ? 'ADMIN' : user.tipo)

  return {
    status: 'success',
    token: access.value,
    remember_token: refresh.value,
    token_expires_at: access.expiresAt.toISOString(),
    id: user.id,
    usuario: user.usuario,
    nombre: user.nombre,
    apellidoP: user.apellidoP,
    apellidoM: user.apellidoM,
    tipo: role,
    rol: role,
    club_asignado: user.club_asignado
  }
})
