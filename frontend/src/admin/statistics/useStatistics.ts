import { useQuery } from '@tanstack/react-query'
import { statisticsService, type StatisticsService } from './statisticsService'

export function useStatistics(service: StatisticsService = statisticsService) {
  return useQuery({ queryKey: ['admin', 'statistics'] as const, queryFn: () => service.get() })
}
