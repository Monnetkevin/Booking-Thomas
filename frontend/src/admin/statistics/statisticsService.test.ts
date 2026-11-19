import { describe, expect, it, vi } from 'vitest'
import type { HttpClient } from '@/lib/http'
import { createStatisticsService } from './statisticsService'

describe('service des statistiques', () => {
  it('lit le rapport et donne le lien de l’export', async () => {
    const client: HttpClient = { request: vi.fn(async <T,>() => ({ data: { from: '2025-12-01' } }) as T) as HttpClient['request'] }
    const service = createStatisticsService(client)

    expect(await service.get()).toEqual({ from: '2025-12-01' })
    expect(client.request).toHaveBeenCalledWith('/admin/statistics')
    expect(service.csvUrl()).toBe('/api/admin/statistics.csv')
  })
})
