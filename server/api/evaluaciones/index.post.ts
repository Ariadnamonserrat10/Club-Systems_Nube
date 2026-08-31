import { z } from 'zod'
import { requireUser } from '../../utils/auth'
import { db } from '../../utils/db'
import { audit } from '../../utils/audit'

const score = z.coerce.number().int().min(0).max(5)
const schema = z.object({
  nombre_estudiante: z.string().trim().min(1).max(255), nombre_club: z.string().trim().min(1).max(255), periodo_realizacion: z.iso.date(),
  criterio_1: score, criterio_2: score, criterio_3: score, criterio_4: score, criterio_5: score, criterio_6: score, criterio_7: score,
  observaciones: z.string().max(2000).default(''), valor_numerico: score, nivel_desempeno: score
})

export default defineEventHandler(async (event) => {
  const user = await requireUser(event, ['SUPERADMIN', 'ADMIN', 'MONITOR'])
  const parsed = schema.safeParse(await readBody(event))
  if (!parsed.success) throw createError({ statusCode: 422, statusMessage: 'Evaluación inválida' })
  if (user.rol === 'MONITOR') {
    const [allowed] = await db().execute<any[]>('SELECT c.id FROM clubs c WHERE c.id = ? AND UPPER(c.nombre) = UPPER(?) LIMIT 1', [user.club_asignado, parsed.data.nombre_club])
    if (!allowed.length) throw createError({ statusCode: 403, statusMessage: 'Solo puedes evaluar alumnos de tu club' })
  }
  const value = parsed.data
  const [result] = await db().execute(
    `INSERT INTO evaluaciones (nombre_estudiante, nombre_club, periodo_realizacion, criterio_1, criterio_2, criterio_3, criterio_4, criterio_5, criterio_6, criterio_7, observaciones, valor_numerico, nivel_desempeno)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    [value.nombre_estudiante, value.nombre_club, value.periodo_realizacion, value.criterio_1, value.criterio_2, value.criterio_3, value.criterio_4, value.criterio_5, value.criterio_6, value.criterio_7, value.observaciones, value.valor_numerico, value.nivel_desempeno])
  const id = Number((result as { insertId: number }).insertId)
  await audit(user.id, 'EVALUACION_CREADA', `Evaluación ${id}: ${value.nombre_estudiante}`)
  return { status: 'success', message: 'Evaluación guardada correctamente', id }
})
