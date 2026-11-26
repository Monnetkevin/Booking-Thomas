/** Modes de prix d'une option (même liste que l'API) ; seule l'option à l'unité a une quantité. */
export const EXTRA_PRICINGS = ['per_stay', 'per_night', 'per_guest', 'per_guest_night', 'per_unit'] as const
export type ExtraPricing = (typeof EXTRA_PRICINGS)[number]

export interface Extra {
  id: number
  name_fr: string
  name_en: string | null
  description_fr: string | null
  description_en: string | null
  /** Centimes ; 0 pour une option gratuite. */
  price: number
  pricing: ExtraPricing
  max_quantity: number
  /** Commande possible jusqu'à ce nombre d'heures avant l'arrivée. */
  lead_time_hours: number
  is_active: boolean
  apartments: Array<{ id: number; name: string }>
}

export type ExtraInput = Omit<Extra, 'id' | 'apartments'> & { apartment_ids: number[] }

/** Chiffres du mois en cours (au mois d'arrivée), montants en centimes. */
export interface ExtraFigures {
  active: number
  total: number
  bookings: number
  with_extras: number
  revenue: number
  previous_revenue: number
}

export interface ExtraCatalog {
  data: Extra[]
  meta: { figures: ExtraFigures }
}

/** Options d'une prochaine arrivée, à préparer. */
export interface ExtraToPrepare {
  reference: string
  arrival: string
  apartment: string
  items: Array<{ name: string; quantity: number }>
}
