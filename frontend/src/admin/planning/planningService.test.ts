import { describe, expect, it, vi } from 'vitest'
import type { HttpClient } from '@/lib/http'
import { createPlanningService } from './planningService'

describe('service du planning', () => {
  it('demande la période voulue', async () => {
    const client: HttpClient = { request: vi.fn(async <T,>() => ({ data: {} }) as T) as HttpClient['request'] }

    await createPlanningService(client).get('2026-11-20', 14)

    expect(client.request).toHaveBeenCalledWith('/admin/planning?from=2026-11-20&days=14')
  })
})
