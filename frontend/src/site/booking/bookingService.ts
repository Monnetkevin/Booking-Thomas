import { http, type HttpClient } from '@/lib/http'
import type { BookingTracking } from './types'

export interface BookingService {
  /** Réservation d'un voyageur ; la signature vient du lien reçu par e-mail. */
  track(reference: string, signature: string): Promise<BookingTracking>
}

export function createBookingService(client: HttpClient): BookingService {
  return {
    track: async (reference, signature) =>
      (
        await client.request<{ data: BookingTracking }>(
          `/bookings/${encodeURIComponent(reference)}?${new URLSearchParams({ signature })}`,
        )
      ).data,
  }
}

export const bookingService = createBookingService(http)
