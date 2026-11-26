import { describe, expect, it, vi } from 'vitest'
import type { HttpClient } from '@/lib/http'
import { createCouponService } from './couponService'

describe('service des codes promo', () => {
  it('lit, crée et modifie aux bons endpoints', async () => {
    const request = vi.fn(async <T,>() => ({ data: [] }) as T)
    const service = createCouponService({ request } as HttpClient)
    const input = {
      code: 'DIX', target: 'stay' as const, type: 'fixed' as const, value: 1000, extra_id: null, free_quantity: null, starts_on: null, ends_on: null,
      max_uses: null, max_uses_per_email: null, min_nights: null, min_amount: null, automatic: false, is_active: true, apartment_ids: [],
    }

    await service.list()
    await service.create(input)
    await service.update(3, input)

    expect(request.mock.calls).toEqual([
      ['/admin/coupons'],
      ['/admin/coupons', { method: 'POST', body: input }],
      ['/admin/coupons/3', { method: 'PUT', body: input }],
    ])
  })
})
