import { http, type DownloadedFile, type FileDownloader, type HttpClient } from '@/lib/http'
import type { Statistics, StatisticsPeriod } from './types'

export interface StatisticsService {
  get(period: StatisticsPeriod): Promise<Statistics>
  /** Export CSV, téléchargé par le client HTTP (session expirée → 401 comme ailleurs). */
  downloadCsv(period: StatisticsPeriod): Promise<DownloadedFile>
}

/** Statistiques (6 mois, 12 mois ou tout), sans React ni cache. */
export function createStatisticsService(client: HttpClient & FileDownloader): StatisticsService {
  return {
    get: async (period) => (await client.request<{ data: Statistics }>(`/admin/statistics?period=${period}`)).data,
    downloadCsv: (period) => client.download(`/admin/statistics.csv?period=${period}`),
  }
}

export const statisticsService = createStatisticsService(http)
