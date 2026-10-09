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

// Quanto pagar aos vendedores no período (comissão + vale + bônus de meta).
export function getComissoes({ inicio, fim }) {
  return http.get('/comissoes', { params: { tipo: 'periodo', inicio, fim } })
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

export function getComissao({ tipo = 'semana', ref, vendedorId } = {}) {
  return http.get('/comissao', { params: { tipo, ref: ref || undefined, vendedor_id: vendedorId || undefined } })
}
