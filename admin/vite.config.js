import { fileURLToPath, URL } from 'node:url'

import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { defineConfig, loadEnv } from 'vite'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')

  return {
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
      // Encaminha as chamadas de API pro backend Laravel, evitando 404 no
      // login (o axios usa baseURL relativa "/api" quando VITE_API_URL não
      // está definida, e sem isso ela batia no próprio Vite, não no Nginx).
      // Alvo padrão é o container "webserver" (nome do serviço no
      // docker-compose.yml, resolvido pela rede interna do Docker). Rodando
      // o painel fora de Docker, defina VITE_API_PROXY_TARGET em
      // admin/.env.local (ex.: http://localhost:8090).
      proxy: {
        '/api': {
          target: env.VITE_API_PROXY_TARGET || 'http://webserver',
          changeOrigin: true,
        },
      },
    },
  }
})
