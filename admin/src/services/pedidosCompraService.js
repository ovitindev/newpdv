import { http } from '@/services/http'

export function getPedidosCompra() {
  return http.get('/pedidos-compra')
}

export function createPedidoCompra(payload) {
  return http.post('/pedidos-compra', payload)
}

export function updatePedidoCompra(id, payload) {
  return http.put(`/pedidos-compra/${id}`, payload)
}

export function deletePedidoCompra(id) {
  return http.delete(`/pedidos-compra/${id}`)
}
