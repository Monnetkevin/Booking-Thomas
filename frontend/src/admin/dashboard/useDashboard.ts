import { useQuery } from '@tanstack/react-query'
import { dashboardService, type DashboardService } from './dashboardService'

export function useDashboard(service: DashboardService = dashboardService) {
  return useQuery({ queryKey: ['admin', 'dashboard'] as const, queryFn: () => service.get(), refetchInterval: 5 * 60_000 })
}
