import { mockRequest } from '@/utils/mockRequest'
import { financeSummary, cashFlow, contasPagar, contasReceber } from '@/data/mock/financeiro'

export function getFinanceOverview() {
  return mockRequest({ financeSummary, cashFlow })
}

export function getContasPagar() {
  return mockRequest(contasPagar)
}

export function getContasReceber() {
  return mockRequest(contasReceber)
}
