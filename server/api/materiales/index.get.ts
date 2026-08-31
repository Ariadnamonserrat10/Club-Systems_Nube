import type { RowDataPacket } from 'mysql2/promise'
import { requireUser } from '../../utils/auth'
import { db } from '../../utils/db'

export default defineEventHandler(async event => {
  await requireUser(event, ['SUPERADMIN', 'ADMIN'])
  const [rows] = await db().query<RowDataPacket[]>(
    `SELECT m.id, m.nombre, m.descripcion, m.cantidad_total, m.cantidad_disponible, m.club_id, m.estado,
            c.nombre club_nombre, m.creado_en, m.actualizado_en
       FROM materiales m LEFT JOIN clubs c ON c.id = m.club_id WHERE m.estado <> 'BAJA' ORDER BY m.nombre`)
  return { status: 'success', data: rows }
})
