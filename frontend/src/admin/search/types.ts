import type { BookingStatus } from '@/admin/bookings/types'

export interface BookingHit {
  reference: string
  guest_name: string
  apartment: string | null
  arrival: string
  departure: string
  status: BookingStatus
}

export interface ApartmentHit {
  id: number
  name: string
  status: string
}

export interface SearchResults {
  bookings: BookingHit[]
  apartments: ApartmentHit[]
}

/** Résultat affiché dans la palette : une page à ouvrir. */
export interface SearchHit {
  id: string
  kind: 'booking' | 'apartment' | 'page'
  title: string
  detail?: string
  to: string
}
