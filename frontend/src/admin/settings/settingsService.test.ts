import { describe, expect, it, vi } from 'vitest'
import type { HttpClient } from '@/lib/http'
import { createSettingsService } from './settingsService'
import type { Settings } from './types'

describe('service des réglages', () => {
  it('lit et enregistre les réglages', async () => {
    const client: HttpClient = { request: vi.fn(async <T,>() => ({ data: {} }) as T) as HttpClient['request'] }
    const service = createSettingsService(client)
    const settings = { min_notice_days: 1 } as Settings

    await service.get()
    await service.update(settings)

    expect(vi.mocked(client.request).mock.calls).toEqual([['/admin/settings'], ['/admin/settings', { method: 'PUT', body: settings }]])
  })
})
