import { http, type HttpClient } from '@/lib/http'
import type { AppNotification, NotificationPage, NotificationPreferences, NotificationPreferencesInput } from './types'

export interface NotificationService {
  list(page: number, unreadOnly: boolean): Promise<NotificationPage>
  unreadCount(): Promise<number>
  markRead(id: string): Promise<AppNotification>
  markAllRead(): Promise<number>
  preferences(): Promise<NotificationPreferences>
  updatePreferences(input: NotificationPreferencesInput): Promise<NotificationPreferences>
}

/** Centre de notifications du compte connecté (hôte ou ménage), sans React ni cache. */
export function createNotificationService(client: HttpClient): NotificationService {
  const unwrap = async <T>(promise: Promise<{ data: T }>) => (await promise).data

  return {
    list: (page, unreadOnly) => {
      const query = new URLSearchParams({ page: String(page), ...(unreadOnly ? { unread: '1' } : {}) })
      return client.request(`/admin/notifications?${query.toString()}`)
    },
    unreadCount: async () => (await unwrap(client.request<{ data: { count: number } }>('/admin/notifications/unread-count'))).count,
    markRead: (id) => unwrap(client.request(`/admin/notifications/${encodeURIComponent(id)}/read`, { method: 'POST' })),
    markAllRead: async () =>
      (await unwrap(client.request<{ data: { marked: number } }>('/admin/notifications/read-all', { method: 'POST' }))).marked,
    preferences: () => unwrap(client.request('/admin/me/notification-preferences')),
    updatePreferences: (input) => unwrap(client.request('/admin/me/notification-preferences', { method: 'PUT', body: input })),
  }
}

export const notificationService = createNotificationService(http)
