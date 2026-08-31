import type { RowDataPacket } from 'mysql2/promise'
import { requireUser } from '../../utils/auth'
import { db } from '../../utils/db'

export default defineEventHandler(async (event) => {
  await requireUser(event)
  const activeOnly = getQuery(event).action === 'activo'
  const [rows] = await db().query<RowDataPacket[]>(`SELECT * FROM periodos ${activeOnly ? "WHERE estado = 'ACTIVO'" : ''} ORDER BY id DESC${activeOnly ? ' LIMIT 1' : ''}`)
  if (activeOnly && !rows[0]) return { status: 'error', message: 'No hay periodo activo', data: null }
  return { status: 'success', data: activeOnly ? rows[0] : rows }
})
