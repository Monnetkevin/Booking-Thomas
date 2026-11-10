import type { Quote } from '@/lib/pricing/types'

export interface PhotoSource {
  width: number
  webp: string | null
  avif: string | null
}

export interface PublicPhoto {
  id: number
  alt: string
  width: number | null
  height: number | null
  sources: Record<'thumb' | 'card' | 'large', PhotoSource>
}

export interface PublicAmenity {
  key: string
  label: string
  icon: string | null
}

/** Carte d'un logement (liste, accueil, recherche). Montants en centimes. */
export interface ApartmentSummary {
  slug: string
  name: string
  tagline: string | null
  city: string
  capacity: number
  bedrooms: number
  beds: number
  bathrooms: number
  surface_m2: number | null
  min_stay: number
  price_from: number | null
  cover: PublicPhoto | null
  amenities: PublicAmenity[]
}

export interface PublicRate {
  label: string | null
  starts_on: string
  ends_on: string
  nightly_price: number
  min_stay: number
  weekend_supplement: number | null
}

export interface ApartmentDetail extends ApartmentSummary {
  description: string | null
  check_in_time: string
  check_out_time: string
  max_stay: number
  cleaning_fee: number
  registration_number: string | null
  cancellation: {
    policy: 'flexible' | 'moderate' | 'strict'
    tiers: Array<{ days_before: number; refund_percent: number }>
  }
  /** Rue sans numéro et position arrondie : jamais l'adresse exacte. */
  location: {
    street: string
    postal_code: string
    city: string
    latitude: number | null
    longitude: number | null
  }
  photos: PublicPhoto[]
  rates: PublicRate[]
}

/** Résultat de recherche par dates. */
export interface SearchResult extends ApartmentSummary {
  available: boolean
  quote: Quote | null
  unavailable_reason: string | null
}

/** Calendrier d'un logement : aide à la saisie, le devis revérifie tout. */
export interface AvailabilityCalendar {
  from: string
  to: string
  first_arrival: string
  last_arrival: string
  min_stay: number
  max_stay: number
  /** Nuits qu'aucun séjour ne peut contenir (AAAA-MM-JJ). */
  unavailable: string[]
  periods: Array<{ starts_on: string; ends_on: string; min_stay: number; arrival_days: number[] | null }>
}

export interface SearchCriteria {
  arrival: string
  departure: string
  guests: number
}
