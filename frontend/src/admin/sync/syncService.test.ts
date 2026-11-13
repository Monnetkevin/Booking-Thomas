import { describe, expect, it, vi } from 'vitest'
import type { HttpClient } from '@/lib/http'
import { createSyncService } from './syncService'

function fakeClient(): HttpClient {
  return { request: vi.fn(async <T,>() => ({ data: {} }) as T) as HttpClient['request'] }
}

describe('service des calendriers', () => {
  it('crée le calendrier d’un logement avec son URL d’import', async () => {
    const client = fakeClient()

    await createSyncService(client).create(4, 'airbnb', 'https://www.airbnb.fr/calendar/ical/1.ics')

    expect(client.request).toHaveBeenCalledWith('/admin/apartments/4/ical-feeds', {
      method: 'POST',
      body: { provider: 'airbnb', import_url: 'https://www.airbnb.fr/calendar/ical/1.ics' },
    })
  })

  it('appelle chaque action sur le bon calendrier', async () => {
    const client = fakeClient()
    const service = createSyncService(client)

    await service.update(7, { is_active: false })
    await service.syncNow(7)
    await service.logs(7)
    await service.regenerateExportToken(7)

    expect(vi.mocked(client.request).mock.calls).toEqual([
      ['/admin/ical-feeds/7', { method: 'PATCH', body: { is_active: false } }],
      ['/admin/ical-feeds/7/sync', { method: 'POST' }],
      ['/admin/ical-feeds/7/logs'],
      ['/admin/ical-feeds/7/export-token', { method: 'POST' }],
    ])
  })
})
