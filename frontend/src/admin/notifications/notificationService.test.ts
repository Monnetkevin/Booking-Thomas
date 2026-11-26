import { describe, expect, it, vi } from 'vitest'
import type { HttpClient } from '@/lib/http'
import { createNotificationService } from './notificationService'

function fakeClient(data: unknown = {}): HttpClient {
  return { request: vi.fn(async <T,>() => ({ data, meta: {} }) as T) as HttpClient['request'] }
}

describe('service des notifications', () => {
  it('liste une page, toutes ou seulement les non lues', async () => {
    const client = fakeClient([])
    const service = createNotificationService(client)

    await service.list(1, false)
    await service.list(2, true)

    expect(vi.mocked(client.request).mock.calls).toEqual([
      ['/admin/notifications?page=1'],
      ['/admin/notifications?page=2&unread=1'],
    ])
  })

  it('lit le nombre de non lues et marque comme lu', async () => {
    const client = fakeClient({ count: 3, marked: 3 })
    const service = createNotificationService(client)

    expect(await service.unreadCount()).toBe(3)
    await service.markRead('9b2c-uuid')
    expect(await service.markAllRead()).toBe(3)

    expect(vi.mocked(client.request).mock.calls.slice(1)).toEqual([
      ['/admin/notifications/9b2c-uuid/read', { method: 'POST' }],
      ['/admin/notifications/read-all', { method: 'POST' }],
    ])
  })

  it('lit et enregistre les préférences du compte', async () => {
    const client = fakeClient()
    const service = createNotificationService(client)

    await service.preferences()
    await service.updatePreferences({ email: { ical_feed_failing: false }, summary_hour: 19 })

    expect(vi.mocked(client.request).mock.calls).toEqual([
      ['/admin/me/notification-preferences'],
      ['/admin/me/notification-preferences', { method: 'PUT', body: { email: { ical_feed_failing: false }, summary_hour: 19 } }],
    ])
  })
})
