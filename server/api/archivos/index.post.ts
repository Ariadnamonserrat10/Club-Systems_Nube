import { mkdir, writeFile } from 'node:fs/promises'
import { join } from 'node:path'
import { randomBytes } from 'node:crypto'
import { requireUser } from '../../utils/auth'
import { audit } from '../../utils/audit'

const allowed: Record<string, { extension: string; signature: (data: Buffer) => boolean }> = {
  'image/jpeg': { extension: 'jpg', signature: data => data[0] === 0xff && data[1] === 0xd8 && data[2] === 0xff },
  'image/png': { extension: 'png', signature: data => data.subarray(0, 8).equals(Buffer.from([137, 80, 78, 71, 13, 10, 26, 10])) },
  'image/webp': { extension: 'webp', signature: data => data.subarray(0, 4).toString() === 'RIFF' && data.subarray(8, 12).toString() === 'WEBP' }
}

export default defineEventHandler(async event => {
  const user = await requireUser(event, ['SUPERADMIN', 'ADMIN'])
  const parts = await readMultipartFormData(event)
  const file = parts?.find(part => part.name === 'foto' && part.filename)
  if (!file) throw createError({ statusCode: 400, statusMessage: 'Archivo "foto" requerido' })
  if (file.data.length > 2 * 1024 * 1024) throw createError({ statusCode: 413, statusMessage: 'La imagen no debe exceder 2 MB' })
  const definition = allowed[file.type || '']
  if (!definition || !definition.signature(file.data)) throw createError({ statusCode: 415, statusMessage: 'Solo se permiten imágenes JPG, PNG o WebP válidas' })
  const filename = `${randomBytes(16).toString('hex')}.${definition.extension}`
  const directory = join(process.cwd(), '.data', 'uploads', 'profiles')
  await mkdir(directory, { recursive: true })
  await writeFile(join(directory, filename), file.data, { flag: 'wx' })
  await audit(user.id, 'FOTO_SUBIDA', `Archivo de perfil ${filename}`)
  return { status: 'success', file: `/api/archivos/${filename}`, filename }
})
