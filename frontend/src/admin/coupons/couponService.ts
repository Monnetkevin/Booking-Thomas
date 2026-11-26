import { http, type HttpClient } from '@/lib/http'
import type { Coupon, CouponInput } from './types'

export interface CouponService {
  list(): Promise<Coupon[]>
  create(input: CouponInput): Promise<Coupon>
  update(id: number, input: CouponInput): Promise<Coupon>
}

/** Codes promo et offres, sans React ni cache. */
export function createCouponService(client: HttpClient): CouponService {
  const unwrap = async <T,>(promise: Promise<{ data: T }>) => (await promise).data

  return {
    list: () => unwrap(client.request('/admin/coupons')),
    create: (input) => unwrap(client.request('/admin/coupons', { method: 'POST', body: input })),
    update: (id, input) => unwrap(client.request(`/admin/coupons/${id}`, { method: 'PUT', body: input })),
  }
}

export const couponService = createCouponService(http)
