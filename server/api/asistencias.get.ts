import type { RowDataPacket } from 'mysql2/promise'
import { requireUser } from '../utils/auth'
import { db } from '../utils/db'
import { positiveId } from '../utils/validation'

interface AttendanceRow extends RowDataPacket { id_alumno: number; fecha: string; presente: number }

export default defineEventHandler(async (event) => {
  await requireUser(event)
  const clubId = positiveId(getQuery(event).club_id)
  const [students] = await db().execute<RowDataPacket[]>(
    `SELECT a.id, a.nombre, a.apellidoP, a.apellidoM, a.numeroControl, a.carrera_id, a.semestre_id, a.id_club
       FROM alumnos a JOIN periodos p ON p.id = a.periodo_id
      WHERE a.id_club = ? AND p.estado = 'ACTIVO'
      ORDER BY a.apellidoP, a.apellidoM, a.nombre`, [clubId])
  if (!students.length) return { status: 'success', data: { fechas: [], asistencias: {}, alumnos: [] } }
  const ids = students.map(row => Number(row.id))
  const placeholders = ids.map(() => '?').join(',')
  const [rows] = await db().execute<AttendanceRow[]>(
    `SELECT id_alumno, DATE_FORMAT(fecha, '%Y-%m-%d') fecha, presente FROM asistencias WHERE id_alumno IN (${placeholders}) ORDER BY fecha`, ids)
  const fechas = [...new Set(rows.map(row => row.fecha))]
  const asistencias: Record<number, Record<string, boolean>> = {}
  for (const row of rows) (asistencias[row.id_alumno] ||= {})[row.fecha] = Boolean(row.presente)
  return { status: 'success', data: { fechas, asistencias, alumnos: students } }
})
