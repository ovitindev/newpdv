import { mockRequest } from '@/utils/mockRequest'
import {
  dashboardStats,
  salesByPeriod,
  paymentMethodsBreakdown,
  topSellingProducts,
  revenueComparison,
  recentActivity,
  fiscalStatus,
  stockSummary,
  stockTopProducts,
} from '@/data/mock/dashboard'

export function getDashboardOverview() {
  return mockRequest({
    stats: dashboardStats,
    salesByPeriod,
    paymentMethodsBreakdown,
    topSellingProducts,
    revenueComparison,
    recentActivity,
    fiscalStatus,
    stockSummary,
    stockTopProducts,
  })
}
