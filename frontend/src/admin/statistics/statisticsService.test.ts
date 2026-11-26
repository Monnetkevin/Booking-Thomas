import { describe, expect, it, vi } from 'vitest'
import type { FileDownloader, HttpClient } from '@/lib/http'
import { createStatisticsService } from './statisticsService'

describe('service des statistiques', () => {
  it('lit le rapport et télécharge l’export par le client HTTP', async () => {
    const file = { blob: new Blob(['a;b']), filename: 'statistiques.csv' }
    const client: HttpClient & FileDownloader = {
      request: vi.fn(async <T,>() => ({ data: { from: '2025-12-01' } }) as T) as HttpClient['request'],
      download: vi.fn(async () => file),
    }
    const service = createStatisticsService(client)

    expect(await service.get('six_months')).toEqual({ from: '2025-12-01' })
    expect(client.request).toHaveBeenCalledWith('/admin/statistics?period=six_months')
    expect(await service.downloadCsv('all')).toBe(file)
    expect(client.download).toHaveBeenCalledWith('/admin/statistics.csv?period=all')
  })
})
