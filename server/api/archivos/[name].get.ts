import { readFile } from 'node:fs/promises'
import { extname, join } from 'node:path'

const types: Record<string, string> = { '.jpg': 'image/jpeg', '.png': 'image/png', '.webp': 'image/webp' }
export default defineEventHandler(async event => {
  const name = String(getRouterParam(event, 'name') || '')
  if (!/^[a-f0-9]{32}\.(jpg|png|webp)$/.test(name)) throw createError({ statusCode: 404, statusMessage: 'Archivo no encontrado' })
  try {
    const data = await readFile(join(process.cwd(), '.data', 'uploads', 'profiles', name))
    setHeader(event, 'Content-Type', types[extname(name)] || 'application/octet-stream')
    setHeader(event, 'Cache-Control', 'public, max-age=31536000, immutable')
    return data
  } catch (error: any) {
    if (error?.code === 'ENOENT') throw createError({ statusCode: 404, statusMessage: 'Archivo no encontrado' })
    throw error
  }
})
