export type ApartmentStatus = 'draft' | 'published'
export type CancellationPolicy = 'flexible' | 'moderate' | 'strict'

/** Texte traduit : le français est obligatoire, l'anglais retombe dessus s'il est vide. */
export interface Translated {
  fr?: string
  en?: string
}

export interface ApartmentListItem {
  id: number
  slug: string
  name: string
  status: ApartmentStatus
  capacity: number
  min_stay: number
  position: number
  /** Prix par nuit de la période en cours ou de la prochaine, en centimes. */
  base_price: number | null
  cover_url: string | null
}

export interface PhotoUrls {
  webp: string | null
  avif: string | null
}

export interface Photo {
  id: number
  position: number
  alt: { fr: string; en: string }
  width: number | null
  height: number | null
  /** Faux tant que les versions WebP/AVIF sont en cours de génération. */
  ready: boolean
  urls: Record<'thumb' | 'card' | 'large', PhotoUrls>
}

export interface Apartment {
  id: number
  slug: string
  status: ApartmentStatus
  name: Translated
  description: Translated
  address: string
  postal_code: string
  city: string
  latitude: number | null
  longitude: number | null
  capacity: number
  bedrooms: number
  beds: number
  bathrooms: number
  surface_m2: number | null
  check_in_time: string
  check_out_time: string
  min_stay: number
  max_stay: number
  turnover_days: number
  cleaning_fee: number
  cancellation_policy: CancellationPolicy
  registration_number: string | null
  position: number
  amenity_ids: number[]
  photos: Photo[]
  base_price: number | null
  updated_at: string | null
}

/** Corps envoyé à POST/PUT /admin/apartments (montants en centimes). */
export interface ApartmentInput {
  slug?: string | null
  name: Translated
  description: Translated
  address: string
  postal_code: string
  city: string
  latitude: number | null
  longitude: number | null
  capacity: number
  bedrooms: number
  beds: number
  bathrooms: number
  surface_m2: number | null
  check_in_time: string
  check_out_time: string
  min_stay: number
  max_stay: number
  turnover_days: number
  cleaning_fee: number
  cancellation_policy: CancellationPolicy
  registration_number: string | null
}

export interface Amenity {
  id: number
  key: string
  label: { fr: string; en?: string }
  icon: string | null
}
