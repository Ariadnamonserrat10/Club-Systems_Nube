import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    proxy: {
      '/api': {
        target: 'https://club-backend-XXXXXX.BACKEND', // Reemplaza con la URL real de Cloud Run
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/BACKEND/, ''),
      },
    },
  },
})