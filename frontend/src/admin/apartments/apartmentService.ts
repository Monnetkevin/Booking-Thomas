import { http, type HttpClient } from '@/lib/http'
import type { Apartment, ApartmentInput, ApartmentListItem, ApartmentStatus } from './types'

export interface ApartmentService {
  list(status?: ApartmentStatus): Promise<ApartmentListItem[]>
  get(id: number): Promise<Apartment>
  create(input: ApartmentInput): Promise<Apartment>
  update(id: number, input: ApartmentInput): Promise<Apartment>
  publish(id: number): Promise<Apartment>
  unpublish(id: number): Promise<Apartment>
  remove(id: number): Promise<void>
  reorder(ids: number[]): Promise<void>
}

/** Appels d'API des logements (back-office), sans React ni cache. */
export function createApartmentService(client: HttpClient): ApartmentService {
  const base = '/admin/apartments'
  const unwrap = async <T>(promise: Promise<{ data: T }>) => (await promise).data

  return {
    list: (status) => unwrap(client.request(status ? `${base}?status=${status}` : base)),
    get: (id) => unwrap(client.request(`${base}/${id}`)),
    create: (input) => unwrap(client.request(base, { method: 'POST', body: input })),
    update: (id, input) => unwrap(client.request(`${base}/${id}`, { method: 'PUT', body: input })),
    publish: (id) => unwrap(client.request(`${base}/${id}/publish`, { method: 'POST' })),
    unpublish: (id) => unwrap(client.request(`${base}/${id}/unpublish`, { method: 'POST' })),
    remove: (id) => client.request<void>(`${base}/${id}`, { method: 'DELETE' }),
    reorder: (ids) => client.request<void>(`${base}/order`, { method: 'PUT', body: { ids } }),
  }
}

export const apartmentService = createApartmentService(http)
