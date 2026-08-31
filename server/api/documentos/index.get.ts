import type { RowDataPacket } from 'mysql2/promise'
import { requireUser } from '../../utils/auth'
import { db } from '../../utils/db'

export default defineEventHandler(async event => {
  await requireUser(event, ['SUPERADMIN', 'ADMIN'])
  const query = getQuery(event)
  const conditions = ["d.estado = 'ACTIVO'"]; const params: Array<string | number> = []
  if (query.tipo) { conditions.push('d.tipo = ?'); params.push(String(query.tipo)) }
  if (query.alumno_id) { conditions.push('d.alumno_id = ?'); params.push(Number(query.alumno_id)) }
  const limit = Math.min(200, Math.max(1, Number(query.limit || 50)))
  const [rows] = await db().execute<RowDataPacket[]>(
    `SELECT d.id, d.folio, d.tipo, d.alumno_id, d.club_id, d.periodo_id, d.nombre_original, d.mime_type, d.tamano_bytes,
            d.concepto, d.monto, d.creado_en, CONCAT_WS(' ', u.nombre, u.apellidoP) creado_por_nombre
       FROM documentos d JOIN usuarios u ON u.id = d.creado_por
      WHERE ${conditions.join(' AND ')} ORDER BY d.creado_en DESC LIMIT ${limit}`, params)
  return { status: 'success', data: rows }
})
