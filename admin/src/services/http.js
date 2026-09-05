import axios from 'axios'

/** Cliente HTTP único para a API Laravel (`routes/api.php`). */
export const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? '/api',
  timeout: 15000,
  headers: {
    Accept: 'application/json',
  },
})

http.interceptors.request.use((config) => {
  const token = localStorage.getItem('novapdv_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

http.interceptors.response.use(
  (response) => response.data,
  (error) => {
    if (error.response?.status === 401 && window.location.pathname !== '/login') {
      localStorage.removeItem('novapdv_token')
      window.location.href = '/login'
    }
    const message = error.response?.data?.message ?? error.message ?? 'Erro de comunicação com o servidor.'
    return Promise.reject(new Error(message))
  },
)
