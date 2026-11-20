import { http, type HttpClient } from '@/lib/http'
import type { Dashboard, DashboardPeriod } from './types'

export interface DashboardService {
  get(period: DashboardPeriod): Promise<Dashboard>
}

/** Tableau de bord de l'hôte, sans React ni cache. */
export function createDashboardService(client: HttpClient): DashboardService {
  return { get: async (period) => (await client.request<{ data: Dashboard }>(`/admin/dashboard?period=${period}`)).data }
}

export const dashboardService = createDashboardService(http)
