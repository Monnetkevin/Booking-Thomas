import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { dashboardService, type DashboardService } from './dashboardService'
import type { DashboardPeriod } from './types'

export function useDashboard(period: DashboardPeriod, service: DashboardService = dashboardService) {
  return useQuery({
    queryKey: ['admin', 'dashboard', period] as const,
    queryFn: () => service.get(period),
    placeholderData: keepPreviousData,
    refetchInterval: 5 * 60_000,
  })
}
