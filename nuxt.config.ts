export default defineNuxtConfig({
  compatibilityDate: '2026-08-28',
  devtools: { enabled: true },
  css: [
    'bootstrap/dist/css/bootstrap.min.css',
    'bootstrap-icons/font/bootstrap-icons.css',
    '~/assets/css/main.css'
  ],
  runtimeConfig: {
    databaseHost: process.env.DB_HOST || '127.0.0.1',
    databasePort: Number(process.env.DB_PORT || 3306),
    databaseUser: process.env.DB_USER || '',
    databasePassword: process.env.DB_PASSWORD || '',
    databaseName: process.env.DB_NAME || '',
    databaseSslCa: process.env.DB_SSL_CA || '',
    tokenPepper: process.env.TOKEN_PEPPER || '',
    public: { appName: 'SIRCE' }
  },
  nitro: {
    preset: 'node-server',
    externals: {
      inline: ['bcryptjs', 'zod'],
      external: ['mysql2']
    }
  },
  typescript: { strict: true, typeCheck: true }
})
