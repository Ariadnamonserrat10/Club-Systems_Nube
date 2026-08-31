import { readFile } from 'node:fs/promises'
import { join } from 'node:path'
import type { RowDataPacket } from 'mysql2/promise'
import { requireUser } from '../../utils/auth'
import { db } from '../../utils/db'
import { positiveId } from '../../utils/validation'

export default defineEventHandler(async event => {
  await requireUser(event, ['SUPERADMIN', 'ADMIN'])
  const id = positiveId(getRouterParam(event, 'id'))
  const [rows] = await db().execute<RowDataPacket[]>("SELECT nombre_archivo, nombre_original, mime_type FROM documentos WHERE id = ? AND estado = 'ACTIVO' LIMIT 1", [id])
  const document = rows[0]
  if (!document) throw createError({ statusCode: 404, statusMessage: 'Documento no encontrado' })
  try {
    const data = await readFile(join(process.cwd(), '.data', 'uploads', 'documents', String(document.nombre_archivo)))
    setHeader(event, 'Content-Type', document.mime_type)
    setHeader(event, 'Content-Disposition', `inline; filename*=UTF-8''${encodeURIComponent(String(document.nombre_original))}`)
    setHeader(event, 'Cache-Control', 'private, no-store')
    return data
  } catch (error: any) {
    if (error?.code === 'ENOENT') throw createError({ statusCode: 410, statusMessage: 'El registro existe pero el archivo no está disponible' })
    throw error
  }
})
