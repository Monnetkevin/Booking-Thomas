import { keepPreviousData, useMutation, useQuery } from '@tanstack/react-query'
import { saveFile } from '@/lib/saveFile'
import { statisticsService, type StatisticsService } from './statisticsService'
import type { StatisticsPeriod } from './types'

export function useStatistics(period: StatisticsPeriod, service: StatisticsService = statisticsService) {
  return useQuery({ queryKey: ['admin', 'statistics', period] as const, queryFn: () => service.get(period), placeholderData: keepPreviousData })
}

export function useDownloadStatisticsCsv(service: StatisticsService = statisticsService) {
  return useMutation({ mutationFn: async (period: StatisticsPeriod) => saveFile(await service.downloadCsv(period), 'statistiques.csv') })
}
