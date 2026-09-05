import { fileURLToPath, URL } from 'node:url'

import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue(), tailwindcss()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    host: true,
    port: 5180,
    // Rodando em container com bind mount do Windows: inotify não propaga
    // mudanças de arquivo pro Linux, então o watcher precisa de polling.
    watch: {
      usePolling: true,
      interval: 300,
    },
  },
})
