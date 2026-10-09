import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import { router } from './router'
import './assets/css/main.css'
import './utils/chartSetup'

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')

// Service worker só no build de produção (em dev ele cacheia módulos do Vite
// e atrapalha o hot reload).
if (import.meta.env.PROD && 'serviceWorker' in navigator) {
  window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}))
}
