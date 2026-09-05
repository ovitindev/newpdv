import { http } from '@/services/http'
import { mockRequest } from '@/utils/mockRequest'
import { categorias } from '@/data/mock/categorias'
import { marcas } from '@/data/mock/marcas'
import { movimentacoes } from '@/data/mock/estoque'

export function getProdutos() {
  return http.get('/produtos')
}

export function createProduto(payload) {
  return http.post('/produtos', payload)
}

export function updateProduto(id, payload) {
  return http.put(`/produtos/${id}`, payload)
}

export function deleteProduto(id) {
  return http.delete(`/produtos/${id}`)
}

export function getCategorias() {
  return mockRequest(categorias)
}

export function getMarcas() {
  return mockRequest(marcas)
}

export function getMovimentacoes() {
  return mockRequest(movimentacoes)
}
