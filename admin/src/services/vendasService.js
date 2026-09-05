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

export function createVenda(payload) {
  return http.post('/vendas', payload)
}

export function getDevolucoes() {
  return mockRequest(devolucoes)
}
