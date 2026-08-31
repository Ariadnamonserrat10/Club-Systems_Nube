import { z } from 'zod'
import type { RowDataPacket } from 'mysql2/promise'
import { requireUser } from '../../utils/auth'
import { transaction } from '../../utils/db'
import { audit } from '../../utils/audit'

const schema = z.object({ nombre: z.string().trim().min(1).max(50), fecha_inicio: z.iso.date(), fecha_fin: z.iso.date() }).refine(v => v.fecha_fin >= v.fecha_inicio, { message: 'La fecha final debe ser posterior a la inicial' })

export default defineEventHandler(async (event) => {
  const user = await requireUser(event, ['SUPERADMIN', 'ADMIN'])
  if (getQuery(event).action === 'cerrar') {
    const result = await transaction(async connection => {
      const [periods] = await connection.query<RowDataPacket[]>("SELECT * FROM periodos WHERE estado = 'ACTIVO' ORDER BY id DESC LIMIT 1 FOR UPDATE")
      const current = periods[0]
      if (!current) throw createError({ statusCode: 409, statusMessage: 'No hay periodo activo para cerrar' })
      await connection.execute(
        `UPDATE alumnos a
            SET a.estado_periodo = CASE
              WHEN (SELECT COUNT(*) FROM asistencias asi WHERE asi.id_alumno = a.id AND asi.presente = 0) >= 3 THEN 'REPROBADO'
              ELSE 'ACREDITADO' END
          WHERE a.periodo_id = ? AND a.estado_periodo = 'ACTIVO'`, [current.id])
      await connection.execute(
        `INSERT INTO historial_configuracion (periodo_id, clave, valor)
         SELECT ?, clave, valor FROM app_config
         ON DUPLICATE KEY UPDATE valor = VALUES(valor)`, [current.id])
      await connection.execute("UPDATE periodos SET estado = 'CERRADO' WHERE id = ?", [current.id])

      const start = new Date(String(current.fecha_inicio))
      const year = start.getUTCFullYear()
      const lowerName = String(current.nombre).toLocaleLowerCase('es-MX')
      let nextName: string; let nextStart: string; let nextEnd: string
      if (lowerName.includes('enero') || lowerName.includes('ene')) {
        nextName = `Agosto - Diciembre ${year}`; nextStart = `${year}-08-01`; nextEnd = `${year}-12-15`
      } else {
        const nextYear = year + 1
        nextName = `Enero - Junio ${nextYear}`; nextStart = `${nextYear}-01-01`; nextEnd = `${nextYear}-06-15`
      }
      const [created] = await connection.execute("INSERT INTO periodos (nombre, fecha_inicio, fecha_fin, estado) VALUES (?, ?, ?, 'ACTIVO')", [nextName, nextStart, nextEnd])
      return { closedId: Number(current.id), newId: Number((created as { insertId: number }).insertId), nextName }
    })
    await audit(user.id, 'PERIODO_CERRADO', `Periodo ${result.closedId}; nuevo periodo ${result.newId}`)
    return { status: 'success', message: 'Periodo cerrado y nuevo periodo generado con éxito', nuevo_periodo_id: result.newId, nuevo_periodo: result.nextName }
  }
  const parsed = schema.safeParse(await readBody(event))
  if (!parsed.success) throw createError({ statusCode: 422, statusMessage: 'Datos del periodo inválidos' })
  let id = 0
  await transaction(async connection => {
    await connection.execute("UPDATE periodos SET estado = 'CERRADO' WHERE estado = 'ACTIVO'")
    const [result] = await connection.execute("INSERT INTO periodos (nombre, fecha_inicio, fecha_fin, estado) VALUES (?, ?, ?, 'ACTIVO')", [parsed.data.nombre, parsed.data.fecha_inicio, parsed.data.fecha_fin])
    id = Number((result as { insertId: number }).insertId)
  })
  await audit(user.id, 'PERIODO_CREADO', `Periodo ${id}: ${parsed.data.nombre}`)
  return { status: 'success', id }
})
