import { z } from 'zod'
import { requireUser } from '../../utils/auth'
import { db } from '../../utils/db'
import { audit } from '../../utils/audit'
import { textPattern, titleCase } from '../../utils/validation'

const schema = z.object({
  nombre: z.string().trim().min(1).max(100),
  tipo: z.enum(['CULTURAL', 'DEPORTIVO']).default('CULTURAL'),
  descripcion: z.string().trim().min(1).max(255),
  cupo_limite: z.coerce.number().int().min(1).max(50),
  id_responsable: z.union([z.coerce.number().int().positive(), z.null()]).optional()
})

export default defineEventHandler(async (event) => {
  const user = await requireUser(event, ['SUPERADMIN'])
  const parsed = schema.safeParse(await readBody(event))
  if (!parsed.success) throw createError({ statusCode: 422, statusMessage: 'Datos del club inválidos', data: parsed.error.issues })
  const data = { ...parsed.data, nombre: titleCase(parsed.data.nombre), descripcion: titleCase(parsed.data.descripcion) }
  if (!textPattern.test(data.nombre) || !textPattern.test(data.descripcion)) {
    throw createError({ statusCode: 422, statusMessage: 'Nombre y descripción solo deben contener texto' })
  }
  const [result] = await db().execute(
    'INSERT INTO clubs (nombre, tipo, descripcion, cupo_limite, id_responsable) VALUES (?, ?, ?, ?, ?)',
    [data.nombre, data.tipo, data.descripcion, data.cupo_limite, data.id_responsable ?? null]
  )
  const id = Number((result as { insertId: number }).insertId)
  await audit(user.id, 'CLUB_CREADO', `Club ${id}: ${data.nombre}`)
  setResponseStatus(event, 201)
  return { data: { id, ...data } }
})
