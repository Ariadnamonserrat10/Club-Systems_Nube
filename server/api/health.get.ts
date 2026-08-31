import { db } from '../utils/db'

export default defineEventHandler(async () => {
  await db().query('SELECT 1')
  return { status: 'success', service: 'SIRCE Nitro', database: 'connected' }
})
