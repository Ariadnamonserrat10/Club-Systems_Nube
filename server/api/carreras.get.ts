import type { RowDataPacket } from 'mysql2/promise'
import { requireUser } from '../utils/auth'
import { db } from '../utils/db'

export default defineEventHandler(async (event) => {
  await requireUser(event)
  const [rows] = await db().query<RowDataPacket[]>('SELECT id, nombre, abreviatura FROM carreras ORDER BY id')
  return { status: 'success', data: rows }
})
