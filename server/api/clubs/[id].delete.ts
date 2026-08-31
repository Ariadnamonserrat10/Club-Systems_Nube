import { requireUser } from '../../utils/auth'
import { db } from '../../utils/db'
import { audit } from '../../utils/audit'
import { positiveId } from '../../utils/validation'

export default defineEventHandler(async (event) => {
  const user = await requireUser(event, ['SUPERADMIN'])
  const id = positiveId(getRouterParam(event, 'id'))
  const [result] = await db().execute('DELETE FROM clubs WHERE id = ?', [id])
  if (!(result as { affectedRows: number }).affectedRows) throw createError({ statusCode: 404, statusMessage: 'Club no encontrado' })
  await audit(user.id, 'CLUB_ELIMINADO', `Club ${id}`)
  return { status: 'success', ok: true }
})
