import type { RowDataPacket } from 'mysql2/promise'
import { requireUser } from '../../utils/auth'
import { db } from '../../utils/db'

export default defineEventHandler(async (event) => {
  await requireUser(event)
  const query = getQuery(event)
  const key = typeof query.clave === 'string' ? query.clave.trim() : ''
  const periodId = Number(query.periodo_id || 0)

  if (periodId > 0) {
    const sql = key
      ? 'SELECT clave, valor FROM historial_configuracion WHERE periodo_id = ? AND clave = ? LIMIT 1'
      : 'SELECT clave, valor FROM historial_configuracion WHERE periodo_id = ? ORDER BY clave'
    const params = key ? [periodId, key] : [periodId]
    const [rows] = await db().execute<RowDataPacket[]>(sql, params)
    return { status: 'success', data: key ? rows[0] || null : rows }
  }

  const sql = key
    ? 'SELECT clave, valor, actualizado_en FROM app_config WHERE clave = ? LIMIT 1'
    : 'SELECT clave, valor, actualizado_en FROM app_config ORDER BY clave'
  const [rows] = await db().execute<RowDataPacket[]>(sql, key ? [key] : [])
  return { status: 'success', data: key ? rows[0] || null : rows }
})
