import { createHash, randomBytes } from 'node:crypto'
import { mkdir, writeFile } from 'node:fs/promises'
import { join } from 'node:path'
import { z } from 'zod'
import { requireUser } from '../../utils/auth'
import { db } from '../../utils/db'
import { audit } from '../../utils/audit'

const metadataSchema = z.object({ tipo: z.enum(['PAGO', 'REPOSICION_CONSTANCIA', 'MATERIAL', 'OTRO']), alumno_id: z.coerce.number().int().positive().nullish(), club_id: z.coerce.number().int().positive().nullish(), periodo_id: z.coerce.number().int().positive().nullish(), concepto: z.string().trim().max(255).nullish(), monto: z.coerce.number().min(0).max(99999999.99).nullish() })
const signatures: Record<string, { extension: string; valid: (data: Buffer) => boolean }> = {
  'application/pdf': { extension: 'pdf', valid: data => data.subarray(0, 5).toString() === '%PDF-' },
  'image/jpeg': { extension: 'jpg', valid: data => data[0] === 0xff && data[1] === 0xd8 && data[2] === 0xff },
  'image/png': { extension: 'png', valid: data => data.subarray(0, 8).equals(Buffer.from([137, 80, 78, 71, 13, 10, 26, 10])) }
}
export default defineEventHandler(async event => {
  const user = await requireUser(event, ['SUPERADMIN', 'ADMIN'])
  const parts = await readMultipartFormData(event)
  const file = parts?.find(part => part.name === 'archivo' && part.filename)
  if (!file) throw createError({ statusCode: 400, statusMessage: 'Documento requerido' })
  if (file.data.length > 5 * 1024 * 1024) throw createError({ statusCode: 413, statusMessage: 'El documento no debe exceder 5 MB' })
  const mime = String(file.type || '')
  const format = signatures[mime]
  if (!format || !format.valid(file.data)) throw createError({ statusCode: 415, statusMessage: 'Solo se permiten PDF, JPG o PNG válidos' })
  const rawMetadata = Object.fromEntries((parts || []).filter(part => part.name && !part.filename).map(part => [part.name!, part.data.toString('utf8') || null]))
  const parsed = metadataSchema.safeParse(rawMetadata)
  if (!parsed.success) throw createError({ statusCode: 422, statusMessage: 'Datos del documento inválidos' })
  const filename = `${randomBytes(16).toString('hex')}.${format.extension}`
  const now = new Date(); const folio = `SIRCE-${now.getUTCFullYear()}-${randomBytes(5).toString('hex').toUpperCase()}`
  const directory = join(process.cwd(), '.data', 'uploads', 'documents')
  await mkdir(directory, { recursive: true }); await writeFile(join(directory, filename), file.data, { flag: 'wx' })
  const value = parsed.data
  const [result] = await db().execute(
    `INSERT INTO documentos (folio, tipo, alumno_id, club_id, periodo_id, nombre_original, nombre_archivo, mime_type, tamano_bytes, hash_sha256, concepto, monto, creado_por)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    [folio, value.tipo, value.alumno_id || null, value.club_id || null, value.periodo_id || null, String(file.filename).slice(0, 255), filename, mime, file.data.length, createHash('sha256').update(file.data).digest('hex'), value.concepto || null, value.monto ?? null, user.id])
  const id = Number((result as { insertId: number }).insertId)
  await audit(user.id, 'DOCUMENTO_REGISTRADO', `${folio}, tipo ${value.tipo}`)
  setResponseStatus(event, 201)
  return { status: 'success', data: { id, folio, tipo: value.tipo, tamano_bytes: file.data.length } }
})
