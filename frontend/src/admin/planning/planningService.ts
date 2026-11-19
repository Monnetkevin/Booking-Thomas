import { http, type HttpClient } from '@/lib/http'
import type { Planning } from './types'

export interface PlanningService {
  get(from: string, days: number): Promise<Planning>
}

/** Planning multi-logements, sans React ni cache. */
export function createPlanningService(client: HttpClient): PlanningService {
  return {
    get: async (from, days) => (await client.request<{ data: Planning }>(`/admin/planning?from=${from}&days=${days}`)).data,
  }
}

export const planningService = createPlanningService(http)
