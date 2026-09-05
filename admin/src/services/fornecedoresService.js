import { mockRequest } from '@/utils/mockRequest'
import { fornecedores } from '@/data/mock/fornecedores'

export function getFornecedores() {
  return mockRequest(fornecedores)
}
