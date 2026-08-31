import type { RowDataPacket } from 'mysql2/promise'
import { requireUser } from '../utils/auth'
import { db } from '../utils/db'

export default defineEventHandler(async event => {
  await requireUser(event, ['SUPERADMIN', 'ADMIN'])
  if (getQuery(event).action !== 'candidatos') throw createError({ statusCode: 400, statusMessage: 'Acción inválida' })
  const [periods] = await db().query<RowDataPacket[]>("SELECT id FROM periodos WHERE estado = 'ACTIVO' ORDER BY id DESC LIMIT 1")
  if (!periods[0]) throw createError({ statusCode: 409, statusMessage: 'No hay un periodo activo para reinscripciones' })
  const [rows] = await db().execute<RowDataPacket[]>(
    `SELECT a.*, c.nombre carrera_nombre, cl.nombre club_anterior
       FROM alumnos a LEFT JOIN carreras c ON c.id = a.carrera_id LEFT JOIN clubs cl ON cl.id = a.id_club
      WHERE a.estado_periodo = 'ACREDITADO'
        AND a.id = (SELECT MAX(last.id) FROM alumnos last WHERE last.numeroControl = a.numeroControl)
        AND NOT EXISTS (SELECT 1 FROM alumnos current WHERE current.numeroControl = a.numeroControl AND current.periodo_id = ?)
      ORDER BY a.apellidoP, a.apellidoM, a.nombre`, [periods[0].id])
  return { status: 'success', data: rows }
})
