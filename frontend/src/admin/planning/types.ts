import type { BookingSource, BookingStatus } from '@/admin/bookings/types'

export type PlanningBlockType = 'booking' | 'ical' | 'manual'

/** Nuits occupées [starts_on, ends_on[ d'un logement, telles qu'affichées au planning. */
export interface PlanningBlock {
  id: number
  type: PlanningBlockType
  starts_on: string
  ends_on: string
  has_conflict: boolean
  /** Voyageur (réservation), plateforme (séjour importé) ou motif (fermeture). */
  label: string | null
  booking: { reference: string; status: BookingStatus; source: BookingSource; guests: number } | null
  external_url: string | null
}

export interface PlanningApartment {
  id: number
  name: string
  blocks: PlanningBlock[]
}

export interface Planning {
  from: string
  to: string
  apartments: PlanningApartment[]
}
