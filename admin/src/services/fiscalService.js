import { mockRequest } from '@/utils/mockRequest'
import { fiscalSummary, notasFiscais } from '@/data/mock/fiscal'

export function getFiscalOverview() {
  return mockRequest({ fiscalSummary, notasFiscais })
}

export function getNotasFiscais() {
  return mockRequest(notasFiscais)
}
