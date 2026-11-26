import { http, type HttpClient } from '@/lib/http'
import type { CleaningPeriod, CleaningSchedule } from './types'

export interface CleaningService {
  schedule(days: CleaningPeriod): Promise<CleaningSchedule>
}

/** Planning du ménage, sans React ni cache. */
export function createCleaningService(client: HttpClient): CleaningService {
  return {
    schedule: async (days) => (await client.request<{ data: CleaningSchedule }>(`/admin/cleaning-schedule?days=${days}`)).data,
  }
}

export const cleaningService = createCleaningService(http)
