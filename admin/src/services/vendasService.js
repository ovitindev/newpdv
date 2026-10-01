import { http } from '@/services/http'
import { mockRequest } from '@/utils/mockRequest'
import { devolucoes } from '@/data/mock/vendas'

export function getVendas({ vendedorId, dataInicio, dataFim } = {}) {
  return http.get('/vendas', {
    params: {
      vendedor_id: vendedorId || undefined,
      data_inicio: dataInicio || undefined,
      data_fim: dataFim || undefined,
    },
  })
}

export function getVenda(id) {
  return http.get(`/vendas/${id}`)
}

export function createVenda(payload) {
  return http.post('/vendas', payload)
}

export function updateVenda(id, payload) {
  return http.put(`/vendas/${id}`, payload)
}

export function deleteVenda(id) {
  return http.delete(`/vendas/${id}`)
}

export function getDevolucoes() {
  return mockRequest(devolucoes)
}
