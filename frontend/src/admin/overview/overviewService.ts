import { http, type HttpClient } from '@/lib/http'
import type { Overview } from './types'

export interface OverviewService {
  get(): Promise<Overview>
}

/** Compteurs de la barre du back-office, sans React ni cache. */
export function createOverviewService(client: HttpClient): OverviewService {
  return { get: async () => (await client.request<{ data: Overview }>('/admin/overview')).data }
}

export const overviewService = createOverviewService(http)
