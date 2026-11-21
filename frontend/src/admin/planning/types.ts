import type { BookingSource, BookingStatus } from '@/admin/bookings/types'

export type PlanningBlockType = 'booking' | 'ical' | 'manual'

/** Nuits occupées [starts_on, ends_on[ d'un logement, telles qu'affichées au planning. */
export interface PlanningBlock {
  id: number
  type: PlanningBlockType
  starts_on: string
  ends_on: string
  has_conflict: boolean
  /** Fermeture posée sur des nuits déjà fermées (site et plateforme) : en retrait, sans alerte. */
  redundant_closure: boolean
  /** Fermeture d'un logement occupé : ménage à prévoir à la fin. */
  needs_cleaning: boolean
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

/** Chiffres de la période, calculés par l'API (mêmes règles que le tableau de bord). */
export interface PlanningFigures {
  sold_nights: number
  open_nights: number
  occupancy: number | null
  arrivals: number
  direct_arrivals: number
  free_nights: number
  free_weekend_nights: number
}

export interface Planning {
  from: string
  to: string
  figures: PlanningFigures
  apartments: PlanningApartment[]
}
