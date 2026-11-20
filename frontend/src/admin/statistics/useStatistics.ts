import { useMutation, useQuery } from '@tanstack/react-query'
import { saveFile } from '@/lib/saveFile'
import { statisticsService, type StatisticsService } from './statisticsService'

export function useStatistics(service: StatisticsService = statisticsService) {
  return useQuery({ queryKey: ['admin', 'statistics'] as const, queryFn: () => service.get() })
}

export function useDownloadStatisticsCsv(service: StatisticsService = statisticsService) {
  return useMutation({ mutationFn: async () => saveFile(await service.downloadCsv(), 'statistiques.csv') })
}
