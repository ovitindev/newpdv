import { http } from '@/services/http'

export function getClientes() {
  return http.get('/clientes')
}

export function createCliente(payload) {
  return http.post('/clientes', payload)
}

export function updateCliente(id, payload) {
  return http.put(`/clientes/${id}`, payload)
}

export function deleteCliente(id) {
  return http.delete(`/clientes/${id}`)
}
