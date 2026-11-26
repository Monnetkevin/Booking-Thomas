import { describe, expect, it, vi } from 'vitest'
import type { HttpClient } from '@/lib/http'
import { createCleaningService } from './cleaningService'

describe('service du planning du ménage', () => {
  it('demande la période voulue et renvoie le planning', async () => {
    const schedule = { from: '2026-11-20', to: '2026-12-03', days: [] }
    const client: HttpClient = { request: vi.fn(async <T,>() => ({ data: schedule }) as T) as HttpClient['request'] }

    expect(await createCleaningService(client).schedule(30)).toEqual(schedule)
    expect(client.request).toHaveBeenCalledWith('/admin/cleaning-schedule?days=30')
  })
})
