import { defineStore } from 'pinia'
import { http } from '@/services/http'

const TOKEN_KEY = 'novapdv_token'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    empresa: null,
    token: localStorage.getItem(TOKEN_KEY),
    ready: false,
  }),
  getters: {
    isAuthenticated: (state) => Boolean(state.token && state.user),
    firstName: (state) => state.user?.nome?.split(' ')[0] ?? '',
    greeting: () => {
      const hour = new Date().getHours()
      if (hour < 12) return 'Bom dia'
      if (hour < 18) return 'Boa tarde'
      return 'Boa noite'
    },
  },
  actions: {
    async login(email, password) {
      const data = await http.post('/login', { email, password })
      this.setSession(data)
      return data
    },
    async logout() {
      try {
        await http.post('/logout')
      } catch {
        // sessão já pode ter expirado no servidor; segue com o logout local
      }
      this.clearSession()
    },
    async fetchMe() {
      if (!this.token) {
        this.ready = true
        return
      }
      try {
        const data = await http.get('/me')
        this.user = data.user
        this.empresa = data.empresa
      } catch {
        this.clearSession()
      } finally {
        this.ready = true
      }
    },
    setSession(data) {
      this.token = data.token
      this.user = data.user
      this.empresa = data.empresa
      localStorage.setItem(TOKEN_KEY, data.token)
    },
    clearSession() {
      this.token = null
      this.user = null
      this.empresa = null
      localStorage.removeItem(TOKEN_KEY)
    },
  },
})
