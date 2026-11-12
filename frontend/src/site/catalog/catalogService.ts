import { http, type HttpClient } from '@/lib/http'
import type { Quote, StayRequest } from '@/lib/pricing/types'
import type { ApartmentDetail, ApartmentSummary, AvailabilityCalendar, SearchCriteria, SearchResult } from './types'

export interface CatalogService {
  list(): Promise<ApartmentSummary[]>
  get(slug: string): Promise<ApartmentDetail>
  calendar(slug: string): Promise<AvailabilityCalendar>
  search(criteria: SearchCriteria): Promise<SearchResult[]>
  quote(slug: string, stay: StayRequest): Promise<Quote>
}

/** Lecture publique des logements et devis, sans React ni cache. */
export function createCatalogService(client: HttpClient): CatalogService {
  const unwrap = async <T>(promise: Promise<{ data: T }>) => (await promise).data

  return {
    list: () => unwrap(client.request('/apartments')),
    get: (slug) => unwrap(client.request(`/apartments/${encodeURIComponent(slug)}`)),
    calendar: (slug) => unwrap(client.request(`/apartments/${encodeURIComponent(slug)}/availability`)),
    search: ({ arrival, departure, guests }) =>
      unwrap(client.request(`/availability?${new URLSearchParams({ arrival, departure, guests: String(guests) })}`)),
    quote: (slug, stay) => unwrap(client.request('/quotes', { method: 'POST', body: { apartment: slug, ...stay } })),
  }
}

export const catalogService = createCatalogService(http)
