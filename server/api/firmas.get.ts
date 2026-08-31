import type { RowDataPacket } from 'mysql2/promise'
import { requireUser } from '../utils/auth'
import { db } from '../utils/db'

export default defineEventHandler(async (event) => {
  await requireUser(event)
  const periodId = Number(getQuery(event).periodo_id || 0)
  const source = periodId > 0 ? 'historial_configuracion' : 'app_config'
  const condition = periodId > 0 ? ' AND periodo_id = ?' : ''
  const params = periodId > 0 ? [periodId] : []
  const [rows] = await db().execute<RowDataPacket[]>(`SELECT clave, valor FROM ${source} WHERE clave IN ('firma_jefe_actividades', 'firma_jefe_promocion', 'firma_jefa_servicios')${condition}`, params)
  const values = Object.fromEntries(rows.map(row => [row.clave, row.valor]))
  const ids = ['firma_jefe_actividades', 'firma_jefe_promocion'].map(key => Number(values[key])).filter(Number.isInteger)
  const users = new Map<number, RowDataPacket>()
  if (ids.length) {
    const [found] = await db().execute<RowDataPacket[]>(`SELECT id, TRIM(CONCAT_WS(' ', nombre, apellidoP, apellidoM)) nombre FROM usuarios WHERE id IN (${ids.map(() => '?').join(',')})`, ids)
    for (const row of found) users.set(Number(row.id), row)
  }
  const resolve = (key: string) => { const raw = values[key]; const found = users.get(Number(raw)); return found ? { id: Number(found.id), nombre: found.nombre } : raw ? { id: null, nombre: String(raw) } : null }
  return { jefe_actividades: resolve('firma_jefe_actividades'), jefe_promocion: resolve('firma_jefe_promocion'), jefa_servicios: resolve('firma_jefa_servicios') }
})
