import type { RowDataPacket } from 'mysql2/promise'
import { requireUser } from '../../utils/auth'
import { db } from '../../utils/db'

export default defineEventHandler(async (event) => {
  await requireUser(event)
  const [rows] = await db().query<RowDataPacket[]>(
    `SELECT c.id, c.nombre, c.tipo, c.descripcion, c.cupo_limite,
            (SELECT COUNT(*) FROM alumnos a JOIN periodos p ON p.id = a.periodo_id
              WHERE a.id_club = c.id AND p.estado = 'ACTIVO') AS cupo_ocupado,
            c.id_responsable, c.creado_en
       FROM clubs c ORDER BY c.id DESC`
  )
  return { data: rows }
})
