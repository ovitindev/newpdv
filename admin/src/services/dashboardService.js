import { http } from '@/services/http'

export function getDashboardOverview() {
  return http.get('/dashboard')
}
