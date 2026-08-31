import { requireUser } from '../../utils/auth'
import { db } from '../../utils/db'
import { audit } from '../../utils/audit'
import { positiveId } from '../../utils/validation'

export default defineEventHandler(async event => {
  const user = await requireUser(event, ['SUPERADMIN'])
  const id = positiveId(getRouterParam(event, 'id'))
  const [result] = await db().execute("UPDATE documentos SET estado = 'ANULADO' WHERE id = ? AND estado = 'ACTIVO'", [id])
  if (!(result as { affectedRows: number }).affectedRows) throw createError({ statusCode: 404, statusMessage: 'Documento no encontrado' })
  await audit(user.id, 'DOCUMENTO_ANULADO', `Documento ${id}`)
  return { status: 'success', message: 'Documento anulado; el archivo se conserva para auditoría' }
})
