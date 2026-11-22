import { http, type HttpClient } from '@/lib/http'
import type { RevenueEntry, RevenueHistoryEntry, RevenueSheet } from './types'

export interface PlatformRevenueService {
  /** Sans mois : le mois précédent, d'après l'horloge du serveur. */
  sheet(month: string | null): Promise<RevenueSheet>
  save(month: string, entries: RevenueEntry[]): Promise<RevenueSheet>
  history(): Promise<RevenueHistoryEntry[]>
}

/** Revenus des plateformes saisis chaque mois, sans React ni cache. */
export function createPlatformRevenueService(client: HttpClient): PlatformRevenueService {
  const unwrap = async <T>(promise: Promise<{ data: T }>) => (await promise).data
  return {
    sheet: (month) => unwrap(client.request(month === null ? '/admin/platform-revenues' : `/admin/platform-revenues?month=${encodeURIComponent(month)}`)),
    save: (month, entries) => unwrap(client.request('/admin/platform-revenues', { method: 'PUT', body: { month, entries } })),
    history: () => unwrap(client.request('/admin/platform-revenues/history')),
  }
}

export const platformRevenueService = createPlatformRevenueService(http)
