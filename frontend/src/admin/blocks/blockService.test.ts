import { describe, expect, it, vi } from 'vitest'
import type { HttpClient } from '@/lib/http'
import { createBlockService } from './blockService'

describe('service des fermetures', () => {
  it('ferme un logement et retire une fermeture', async () => {
    const client: HttpClient = { request: vi.fn(async <T,>() => ({ data: {} }) as T) as HttpClient['request'] }
    const service = createBlockService(client)

    await service.create(3, { starts_on: '2026-12-01', ends_on: '2026-12-05', note: 'Famille', needs_cleaning: true })
    await service.remove(9)

    expect(vi.mocked(client.request).mock.calls).toEqual([
      ['/admin/apartments/3/blocks', { method: 'POST', body: { starts_on: '2026-12-01', ends_on: '2026-12-05', note: 'Famille', needs_cleaning: true } }],
      ['/admin/blocks/9', { method: 'DELETE' }],
    ])
  })
})
