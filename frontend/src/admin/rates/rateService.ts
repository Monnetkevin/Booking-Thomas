import { http, type HttpClient } from '@/lib/http'
import type { Quote, StayRequest, StayViolation } from '@/lib/pricing/types'
import type { RatePeriod, RatePeriodInput } from './types'

export interface RateService {
  list(apartmentId: number, includePast: boolean): Promise<RatePeriod[]>
  create(apartmentId: number, input: RatePeriodInput): Promise<RatePeriod>
  update(periodId: number, input: RatePeriodInput): Promise<RatePeriod>
  remove(periodId: number): Promise<void>
  preview(apartmentId: number, stay: StayRequest): Promise<QuotePreview>
}

export interface QuotePreview {
  quote: Quote | null
  warnings: StayViolation[]
}

/** Périodes tarifaires (back-office), sans React ni cache. */
export function createRateService(client: HttpClient): RateService {
  const unwrap = async <T>(promise: Promise<{ data: T }>) => (await promise).data

  return {
    list: (apartmentId, includePast) =>
      unwrap(client.request(`/admin/apartments/${apartmentId}/rate-periods${includePast ? '?include_past=1' : ''}`)),
    create: (apartmentId, input) =>
      unwrap(client.request(`/admin/apartments/${apartmentId}/rate-periods`, { method: 'POST', body: input })),
    update: (periodId, input) => unwrap(client.request(`/admin/rate-periods/${periodId}`, { method: 'PUT', body: input })),
    remove: (periodId) => client.request<void>(`/admin/rate-periods/${periodId}`, { method: 'DELETE' }),
    preview: (apartmentId, stay) =>
      unwrap(client.request(`/admin/apartments/${apartmentId}/quote-preview`, { method: 'POST', body: stay })),
  }
}

export const rateService = createRateService(http)
