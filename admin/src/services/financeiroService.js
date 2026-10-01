import { http } from '@/services/http'

export function getContasPagar() {
  return http.get('/contas-pagar')
}

export function createContaPagar(payload) {
  return http.post('/contas-pagar', payload)
}

export function updateContaPagar(id, payload) {
  return http.put(`/contas-pagar/${id}`, payload)
}

export function deleteContaPagar(id) {
  return http.delete(`/contas-pagar/${id}`)
}

export function replicarContasPagar() {
  return http.post('/contas-pagar/replicar')
}

export function getContasReceber() {
  return http.get('/contas-receber')
}

export function createContaReceber(payload) {
  return http.post('/contas-receber', payload)
}

export function updateContaReceber(id, payload) {
  return http.put(`/contas-receber/${id}`, payload)
}

export function deleteContaReceber(id) {
  return http.delete(`/contas-receber/${id}`)
}

export function replicarContasReceber() {
  return http.post('/contas-receber/replicar')
}
