import { http, type HttpClient } from '@/lib/http'
import type { Dashboard } from './types'

export interface DashboardService {
  get(): Promise<Dashboard>
}

/** Tableau de bord de l'hôte, sans React ni cache. */
export function createDashboardService(client: HttpClient): DashboardService {
  return { get: async () => (await client.request<{ data: Dashboard }>('/admin/dashboard')).data }
}

export const dashboardService = createDashboardService(http)
