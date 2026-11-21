import { describe, expect, it, vi } from 'vitest'
import type { HttpClient } from '@/lib/http'
import { createOverviewService } from './overviewService'

describe('service des compteurs de la barre', () => {
  it('lit les compteurs', async () => {
    const data = { conflicts: 1, failing_feeds: 0, unread_messages: 2, last_sync_at: null }
    const client: HttpClient = { request: vi.fn(async <T,>() => ({ data }) as T) as HttpClient['request'] }

    expect(await createOverviewService(client).get()).toEqual(data)
    expect(client.request).toHaveBeenCalledWith('/admin/overview')
  })
})
