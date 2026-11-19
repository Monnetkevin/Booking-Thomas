import { describe, expect, it, vi } from 'vitest'
import type { HttpClient } from '@/lib/http'
import { createDashboardService } from './dashboardService'

describe('service du tableau de bord', () => {
  it('lit le résumé', async () => {
    const client: HttpClient = { request: vi.fn(async <T,>() => ({ data: { today: '2026-11-20' } }) as T) as HttpClient['request'] }

    expect(await createDashboardService(client).get()).toEqual({ today: '2026-11-20' })
    expect(client.request).toHaveBeenCalledWith('/admin/dashboard')
  })
})
