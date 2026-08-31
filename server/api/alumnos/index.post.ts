import { z } from 'zod'
import type { RowDataPacket } from 'mysql2/promise'
import { requireUser } from '../../utils/auth'
import { db } from '../../utils/db'
import { audit } from '../../utils/audit'
import { textPattern, titleCase } from '../../utils/validation'

const schema = z.object({
  nombre: z.string().trim().min(1).max(50), apellidoP: z.string().trim().min(1).max(50), apellidoM: z.string().trim().min(1).max(50),
  numeroControl: z.coerce.string().regex(/^\d{8}$/), telefono: z.coerce.string().regex(/^\d{7,15}$/),
  carrera_id: z.coerce.number().int().positive(), semestre_id: z.coerce.number().int().positive(), id_club: z.coerce.number().int().positive()
})

export default defineEventHandler(async (event) => {
  const user = await requireUser(event, ['SUPERADMIN', 'ADMIN', 'MONITOR'])
  const parsed = schema.safeParse(await readBody(event))
  if (!parsed.success) throw createError({ statusCode: 422, statusMessage: 'Datos del alumno inválidos' })
  if (user.rol === 'MONITOR' && Number(user.club_asignado) !== parsed.data.id_club) throw createError({ statusCode: 403, statusMessage: 'Este club no está asignado al monitor' })
  const names = [parsed.data.nombre, parsed.data.apellidoP, parsed.data.apellidoM].map(titleCase)
  if (names.some(value => !textPattern.test(value))) throw createError({ statusCode: 422, statusMessage: 'Los nombres solo deben contener letras' })
  const [periods] = await db().query<RowDataPacket[]>("SELECT id FROM periodos WHERE estado = 'ACTIVO' ORDER BY id DESC LIMIT 1")
  if (!periods[0]) throw createError({ statusCode: 403, statusMessage: 'No hay periodo activo' })
  try {
    const [result] = await db().execute(
      `INSERT INTO alumnos (nombre, apellidoP, apellidoM, numeroControl, telefono, carrera_id, semestre_id, id_club, periodo_id, estado_periodo)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVO')`, [...names, parsed.data.numeroControl, parsed.data.telefono, parsed.data.carrera_id, parsed.data.semestre_id, parsed.data.id_club, periods[0].id])
    const id = Number((result as { insertId: number }).insertId)
    await audit(user.id, 'ALUMNO_CREADO', `Alumno ${id}, control ${parsed.data.numeroControl}`)
    setResponseStatus(event, 201)
    return { status: 'success', data: { id, ...parsed.data, nombre: names[0], apellidoP: names[1], apellidoM: names[2] } }
  } catch (error: any) {
    if (error?.code === 'ER_DUP_ENTRY') throw createError({ statusCode: 409, statusMessage: 'El número de control ya está registrado en el periodo activo' })
    throw error
  }
})
