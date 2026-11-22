import { describe, expect, it, vi } from 'vitest'
import type { HttpClient } from '@/lib/http'
import { createPlatformRevenueService } from './platformRevenueService'

describe('service des revenus des plateformes', () => {
  it('demande la feuille du mois et envoie les montants en JSON', async () => {
    const client: HttpClient = { request: vi.fn(async <T,>() => ({ data: {} }) as T) as HttpClient['request'] }
    const service = createPlatformRevenueService(client)

    await service.sheet('2026-10')
    await service.save('2026-10', [{ apartment_id: 3, platform: 'airbnb', net_amount: 155207 }])

    expect(vi.mocked(client.request).mock.calls[0]?.[0]).toBe('/admin/platform-revenues?month=2026-10')
    expect(vi.mocked(client.request).mock.calls[1]).toEqual([
      '/admin/platform-revenues',
      { method: 'PUT', body: { month: '2026-10', entries: [{ apartment_id: 3, platform: 'airbnb', net_amount: 155207 }] } },
    ])
  })
})
