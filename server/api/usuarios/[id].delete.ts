import { requireUser } from '../../utils/auth'
import { db } from '../../utils/db'
import { audit } from '../../utils/audit'
import { positiveId } from '../../utils/validation'

export default defineEventHandler(async (event) => {
  const actor = await requireUser(event, ['SUPERADMIN'])
  const id = positiveId(getRouterParam(event, 'id'))
  if (actor.id === id) throw createError({ statusCode: 409, statusMessage: 'No puedes desactivar tu propia cuenta' })
  const [result] = await db().execute('UPDATE usuarios SET activo = 0, club_asignado = NULL WHERE id = ? AND COALESCE(activo, 1) = 1', [id])
  if (!(result as { affectedRows: number }).affectedRows) throw createError({ statusCode: 404, statusMessage: 'Usuario no encontrado o ya desactivado' })
  await db().execute('UPDATE usuario_club SET activo = 0 WHERE usuario_id = ?', [id])
  await audit(actor.id, 'USUARIO_DESACTIVADO', `Usuario ${id}`)
  return { status: 'success', message: 'Usuario desactivado correctamente' }
})
