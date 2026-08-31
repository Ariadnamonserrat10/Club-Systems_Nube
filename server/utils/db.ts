import mysql, { type Pool, type PoolConnection } from 'mysql2/promise'

let pool: Pool | undefined

function databasePool() {
  if (pool) return pool

  const config = useRuntimeConfig()
  if (!config.databaseUser || !config.databaseName) {
    throw createError({
      statusCode: 500,
      statusMessage: 'Falta configurar DB_USER y DB_NAME'
    })
  }

  pool = mysql.createPool({
    host: config.databaseHost,
    port: Number(config.databasePort),
    user: config.databaseUser,
    password: config.databasePassword,
    database: config.databaseName,
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,
    charset: 'utf8mb4',
    ssl: config.databaseSslCa
      ? { ca: config.databaseSslCa, rejectUnauthorized: true }
      : undefined
  })

  return pool
}

export function db() {
  return databasePool()
}

export async function transaction<T>(work: (connection: PoolConnection) => Promise<T>) {
  const connection = await databasePool().getConnection()
  try {
    await connection.beginTransaction()
    const result = await work(connection)
    await connection.commit()
    return result
  } catch (error) {
    await connection.rollback()
    throw error
  } finally {
    connection.release()
  }
}
