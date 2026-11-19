import { http, type HttpClient } from '@/lib/http'
import type { Settings } from './types'

export interface SettingsService {
  get(): Promise<Settings>
  update(settings: Settings): Promise<Settings>
}

/** Réglages de l'hôte, sans React ni cache. */
export function createSettingsService(client: HttpClient): SettingsService {
  const unwrap = async <T>(promise: Promise<{ data: T }>) => (await promise).data

  return {
    get: () => unwrap(client.request('/admin/settings')),
    update: (settings) => unwrap(client.request('/admin/settings', { method: 'PUT', body: settings })),
  }
}

export const settingsService = createSettingsService(http)
