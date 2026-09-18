import { http } from '@/services/http'
import { mockRequest } from '@/utils/mockRequest'
import { usuarios, perfis } from '@/data/mock/usuarios'
import { certificadoA1, configFiscal } from '@/data/mock/empresa'

export function getUsuarios() {
  return mockRequest(usuarios)
}

export function getVendedores() {
  return http.get('/vendedores')
}

export function createVendedor(payload) {
  return http.post('/vendedores', payload)
}

export function updateVendedor(id, payload) {
  return http.put(`/vendedores/${id}`, payload)
}

export function deleteVendedor(id) {
  return http.delete(`/vendedores/${id}`)
}

export function getPerfis() {
  return mockRequest(perfis)
}

export function getEmpresa() {
  return http.get('/empresa')
}

export function updateEmpresa(payload) {
  return http.put('/empresa', payload)
}

export function getCertificadoA1() {
  return mockRequest(certificadoA1)
}

export function getConfigFiscal() {
  return mockRequest(configFiscal)
}
