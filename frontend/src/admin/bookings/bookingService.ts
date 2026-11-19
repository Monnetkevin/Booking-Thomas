import { http, type HttpClient } from '@/lib/http'
import type { BookingDetail, BookingFilters, BookingListPage, GuestInput, ManualBookingInput } from './types'

export interface AdminBookingService {
  list(filters: BookingFilters, page: number): Promise<BookingListPage>
  get(reference: string): Promise<BookingDetail>
  create(input: ManualBookingInput): Promise<BookingDetail>
  update(reference: string, input: GuestInput): Promise<BookingDetail>
  cancel(reference: string, notifyGuest: boolean): Promise<BookingDetail>
  resendConfirmation(reference: string): Promise<void>
  /** Lien direct (cookie de session) vers la confirmation PDF. */
  pdfUrl(reference: string): string
}

/** Paramètres de la liste, sans les filtres vides. */
export function bookingQuery(filters: BookingFilters, page: number): string {
  const params = new URLSearchParams({ page: String(page) })
  for (const [key, value] of Object.entries(filters)) {
    if (value !== undefined && value !== null && value !== '') params.set(key, String(value))
  }
  return params.toString()
}

/** Réservations du back-office, sans React ni cache. */
export function createAdminBookingService(client: HttpClient): AdminBookingService {
  const unwrap = async <T>(promise: Promise<{ data: T }>) => (await promise).data
  const path = (reference: string) => `/admin/bookings/${encodeURIComponent(reference)}`

  return {
    list: (filters, page) => client.request(`/admin/bookings?${bookingQuery(filters, page)}`),
    get: (reference) => unwrap(client.request(path(reference))),
    create: (input) => unwrap(client.request('/admin/bookings', { method: 'POST', body: input })),
    update: (reference, input) => unwrap(client.request(path(reference), { method: 'PATCH', body: input })),
    cancel: (reference, notifyGuest) => unwrap(client.request(`${path(reference)}/cancel`, { method: 'POST', body: { notify_guest: notifyGuest } })),
    resendConfirmation: (reference) => client.request<void>(`${path(reference)}/resend-confirmation`, { method: 'POST' }),
    pdfUrl: (reference) => `/api${path(reference)}/pdf`,
  }
}

export const adminBookingService = createAdminBookingService(http)
