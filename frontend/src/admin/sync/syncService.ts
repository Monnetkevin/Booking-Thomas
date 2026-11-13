import { http, type HttpClient } from '@/lib/http'
import type { IcalFeed, IcalFeedInput, IcalProvider, IcalSyncLog } from './types'

export interface SyncService {
  list(): Promise<IcalFeed[]>
  create(apartmentId: number, provider: IcalProvider, importUrl: string | null): Promise<IcalFeed>
  update(feedId: number, input: IcalFeedInput): Promise<IcalFeed>
  syncNow(feedId: number): Promise<IcalSyncLog>
  logs(feedId: number): Promise<IcalSyncLog[]>
  regenerateExportToken(feedId: number): Promise<IcalFeed>
}

/** Calendriers des plateformes (back-office), sans React ni cache. */
export function createSyncService(client: HttpClient): SyncService {
  const unwrap = async <T>(promise: Promise<{ data: T }>) => (await promise).data

  return {
    list: () => unwrap(client.request('/admin/ical-feeds')),
    create: (apartmentId, provider, importUrl) =>
      unwrap(client.request(`/admin/apartments/${apartmentId}/ical-feeds`, { method: 'POST', body: { provider, import_url: importUrl } })),
    update: (feedId, input) => unwrap(client.request(`/admin/ical-feeds/${feedId}`, { method: 'PATCH', body: input })),
    syncNow: (feedId) => unwrap(client.request(`/admin/ical-feeds/${feedId}/sync`, { method: 'POST' })),
    logs: (feedId) => unwrap(client.request(`/admin/ical-feeds/${feedId}/logs`)),
    regenerateExportToken: (feedId) => unwrap(client.request(`/admin/ical-feeds/${feedId}/export-token`, { method: 'POST' })),
  }
}

export const syncService = createSyncService(http)
