import { http, type DownloadedFile, type FileDownloader, type HttpClient } from '@/lib/http'
import type { Statistics } from './types'

export interface StatisticsService {
  get(): Promise<Statistics>
  /** Export CSV, téléchargé par le client HTTP (session expirée → 401 comme ailleurs). */
  downloadCsv(): Promise<DownloadedFile>
}

/** Statistiques sur 12 mois glissants, sans React ni cache. */
export function createStatisticsService(client: HttpClient & FileDownloader): StatisticsService {
  return {
    get: async () => (await client.request<{ data: Statistics }>('/admin/statistics')).data,
    downloadCsv: () => client.download('/admin/statistics.csv'),
  }
}

export const statisticsService = createStatisticsService(http)
