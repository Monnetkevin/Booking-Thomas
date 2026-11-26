import { describe, expect, it, vi } from 'vitest'
import type { HttpClient } from '@/lib/http'
import { createExtraService } from './extraService'

describe('service des options', () => {
  it('crée, modifie et lit la liste à préparer aux bons endpoints', async () => {
    const request = vi.fn(async <T,>() => ({ data: [] }) as T)
    const service = createExtraService({ request } as HttpClient)
    const input = { name_fr: 'Champagne', name_en: null, description_fr: null, description_en: null, price: 4500, pricing: 'per_unit' as const, max_quantity: 3, lead_time_hours: 48, is_active: true, apartment_ids: [1] }

    await service.catalog()
    await service.create(input)
    await service.update(5, input)
    await service.toPrepare()

    expect(request.mock.calls).toEqual([
      ['/admin/extras'],
      ['/admin/extras', { method: 'POST', body: input }],
      ['/admin/extras/5', { method: 'PUT', body: input }],
      ['/admin/extras/to-prepare'],
    ])
  })
})
