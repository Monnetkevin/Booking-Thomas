import { http, type HttpClient } from '@/lib/http'
import type { Statistics } from './types'

export interface StatisticsService {
  get(): Promise<Statistics>
  /** Lien direct (cookie de session) vers l'export CSV. */
  csvUrl(): string
}

/** Statistiques sur 12 mois glissants, sans React ni cache. */
export function createStatisticsService(client: HttpClient): StatisticsService {
  return {
    get: async () => (await client.request<{ data: Statistics }>('/admin/statistics')).data,
    csvUrl: () => '/api/admin/statistics.csv',
  }
}

export const statisticsService = createStatisticsService(http)
