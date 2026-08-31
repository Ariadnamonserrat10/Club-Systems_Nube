import { z } from 'zod'
import type { RowDataPacket } from 'mysql2/promise'
import { requireUser } from '../utils/auth'
import { transaction } from '../utils/db'
import { audit } from '../utils/audit'

const schema = z.object({ numeroControl: z.coerce.string().regex(/^\d{8}$/), id_club: z.coerce.number().int().positive() })
export default defineEventHandler(async event => {
  const actor = await requireUser(event, ['SUPERADMIN', 'ADMIN'])
  if (getQuery(event).action !== 'inscribir') throw createError({ statusCode: 400, statusMessage: 'Acción inválida' })
  const parsed = schema.safeParse(await readBody(event))
  if (!parsed.success) throw createError({ statusCode: 422, statusMessage: 'Número de control y club son obligatorios' })
  const value = parsed.data
  const id = await transaction(async connection => {
    const [periods] = await connection.query<RowDataPacket[]>("SELECT id FROM periodos WHERE estado = 'ACTIVO' ORDER BY id DESC LIMIT 1 FOR UPDATE")
    const periodId = Number(periods[0]?.id || 0)
    if (!periodId) throw createError({ statusCode: 409, statusMessage: 'No hay periodo activo' })
    const [students] = await connection.execute<RowDataPacket[]>("SELECT * FROM alumnos WHERE numeroControl = ? AND estado_periodo = 'ACREDITADO' ORDER BY id DESC LIMIT 1", [value.numeroControl])
    const student = students[0]
    if (!student) throw createError({ statusCode: 404, statusMessage: 'Alumno no encontrado o no acreditado' })
    const [existing] = await connection.execute<RowDataPacket[]>('SELECT id FROM alumnos WHERE numeroControl = ? AND periodo_id = ? LIMIT 1', [value.numeroControl, periodId])
    if (existing.length) throw createError({ statusCode: 409, statusMessage: 'El alumno ya está inscrito en el periodo activo' })
    const [clubs] = await connection.execute<RowDataPacket[]>(
      `SELECT c.id, c.cupo_limite,
              (SELECT COUNT(*) FROM alumnos a WHERE a.id_club = c.id AND a.periodo_id = ?) cupo_ocupado
         FROM clubs c WHERE c.id = ? FOR UPDATE`, [periodId, value.id_club])
    const club = clubs[0]
    if (!club) throw createError({ statusCode: 404, statusMessage: 'Club no encontrado' })
    if (Number(club.cupo_ocupado) >= Number(club.cupo_limite)) throw createError({ statusCode: 409, statusMessage: 'El club seleccionado ya no tiene cupo' })
    const nextSemester = Math.min(12, Number(student.semestre_id || 0) + 1)
    const [created] = await connection.execute(
      `INSERT INTO alumnos (nombre, apellidoP, apellidoM, numeroControl, telefono, carrera_id, semestre_id, id_club, periodo_id, estado_periodo)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVO')`,
      [student.nombre, student.apellidoP, student.apellidoM, student.numeroControl, student.telefono, student.carrera_id, nextSemester, value.id_club, periodId])
    return Number((created as { insertId: number }).insertId)
  })
  await audit(actor.id, 'ALUMNO_REINSCRITO', `Alumno ${value.numeroControl}, registro ${id}, club ${value.id_club}`)
  return { status: 'success', message: 'Reinscripción exitosa', id }
})
