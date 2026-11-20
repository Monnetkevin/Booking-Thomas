import { describe, expect, it, vi } from 'vitest'
import type { HttpClient } from '@/lib/http'
import { createAuditService } from './auditService'

describe('service du journal', () => {
  it('lit une page, toutes actions ou une seule', async () => {
    const client: HttpClient = { request: vi.fn(async <T,>() => ({ data: [] }) as T) as HttpClient['request'] }
    const service = createAuditService(client)

    await service.list(1, null)
    await service.list(2, 'booking.cancelled')

    expect(vi.mocked(client.request).mock.calls).toEqual([
      ['/admin/audit-logs?page=1'],
      ['/admin/audit-logs?page=2&action=booking.cancelled'],
    ])
  })
})
