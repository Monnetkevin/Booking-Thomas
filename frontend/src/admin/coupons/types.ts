export type CouponTarget = 'stay' | 'extra' | 'both'
export type CouponType = 'percent' | 'fixed' | 'free_extra'

/** Code promo ou offre automatique ; montants en centimes. */
export interface Coupon {
  id: number
  code: string
  target: CouponTarget
  type: CouponType
  /** Pourcentage (1 à 100) ou montant ; 0 pour une option offerte. */
  value: number
  extra: { id: number; name: string } | null
  free_quantity: number
  starts_on: string | null
  ends_on: string | null
  max_uses: number | null
  max_uses_per_email: number | null
  min_nights: number | null
  min_amount: number | null
  automatic: boolean
  is_active: boolean
  apartments: Array<{ id: number; name: string }>
  /** Utilisations qui comptent (réservations expirées exclues). */
  uses: number
  discount_total: number
}

export interface CouponInput {
  code: string
  target: CouponTarget
  type: CouponType
  value: number | null
  extra_id: number | null
  free_quantity: number | null
  starts_on: string | null
  ends_on: string | null
  max_uses: number | null
  max_uses_per_email: number | null
  min_nights: number | null
  min_amount: number | null
  automatic: boolean
  is_active: boolean
  apartment_ids: number[]
}
