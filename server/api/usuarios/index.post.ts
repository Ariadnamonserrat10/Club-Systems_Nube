import bcrypt from 'bcryptjs'
import { z } from 'zod'
import { requireUser } from '../../utils/auth'
import { transaction } from '../../utils/db'
import { audit } from '../../utils/audit'
import { textPattern, titleCase } from '../../utils/validation'

const passwordRule = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8}$/
const usernameRule = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8}$/
const schema = z.object({
  nombre: z.string().trim().min(1).max(50), apellidoP: z.string().trim().min(1).max(50), apellidoM: z.string().trim().max(50).default(''),
  usuario: z.string().regex(usernameRule), password: z.string().regex(passwordRule),
  tipo: z.enum(['OFICINA', 'MONITOR']), numeroControl: z.string().regex(/^\d{8}$/).nullish().or(z.literal('')),
  telefono: z.string().regex(/^\d{7,15}$/).nullish().or(z.literal('')), carrera: z.coerce.number().int().positive().nullish(),
  carrera_id: z.coerce.number().int().positive().nullish(), semestre: z.coerce.number().int().positive().nullish(),
  semestre_id: z.coerce.number().int().positive().nullish(), club_asignado: z.coerce.number().int().positive().nullish(), foto: z.string().max(255).nullish()
}).superRefine((value, context) => {
  if (value.tipo === 'MONITOR' && (!value.numeroControl || !value.telefono || !(value.carrera_id || value.carrera) || !(value.semestre_id || value.semestre))) {
    context.addIssue({ code: 'custom', message: 'Para MONITOR son obligatorios número de control, teléfono, carrera y semestre' })
  }
})

export default defineEventHandler(async (event) => {
  const creator = await requireUser(event, ['SUPERADMIN'])
  const parsed = schema.safeParse(await readBody(event))
  if (!parsed.success) throw createError({ statusCode: 422, statusMessage: 'Datos del usuario inválidos', data: parsed.error.issues })
  const value = parsed.data
  const names = [value.nombre, value.apellidoP, value.apellidoM].map(titleCase)
  if (names.some(name => name && !textPattern.test(name))) throw createError({ statusCode: 422, statusMessage: 'Los nombres solo deben contener letras' })
  const role = value.tipo === 'OFICINA' ? 'ADMIN' : 'MONITOR'
  const password = await bcrypt.hash(value.password, 12)
  const [nombre = '', apellidoP = '', apellidoM = ''] = names
  try {
    const id = await transaction(async connection => {
      const [result] = await connection.execute(
        `INSERT INTO usuarios (nombre, apellidoP, apellidoM, numeroControl, telefono, carrera_id, semestre_id, usuario, password, tipo, club_asignado, foto, rol_id, creado_por, cambio_password_requerido)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, (SELECT id FROM roles WHERE nombre = ? LIMIT 1), ?, 1)`,
        [nombre, apellidoP, apellidoM, value.numeroControl || null, value.telefono || null, value.carrera_id || value.carrera || null,
          value.semestre_id || value.semestre || null, value.usuario, password, value.tipo, value.club_asignado || null, value.foto || null, role, creator.id])
      const userId = Number((result as { insertId: number }).insertId)
      if (value.tipo === 'MONITOR' && value.club_asignado) await connection.execute(
        'INSERT INTO usuario_club (usuario_id, club_id, fecha_asignacion, activo, asignado_por) VALUES (?, ?, CURDATE(), 1, ?)',
        [userId, value.club_asignado, creator.id])
      return userId
    })
    await audit(creator.id, 'USUARIO_CREADO', `Usuario ${id}: ${value.usuario}, rol ${role}`)
    setResponseStatus(event, 201)
    return { status: 'success', message: 'Usuario registrado exitosamente', id }
  } catch (error: any) {
    if (error?.code === 'ER_DUP_ENTRY') throw createError({ statusCode: 409, statusMessage: 'El usuario o número de control ya está registrado' })
    throw error
  }
})
