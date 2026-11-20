export type BookingStatus = 'pending' | 'confirmed' | 'completed' | 'cancelled' | 'expired'
export type BookingSource = 'direct' | 'manual'

export const BOOKING_STATUSES: BookingStatus[] = ['pending', 'confirmed', 'completed', 'cancelled', 'expired']

/** Onglets de la liste : à venir, en cours, terminées, annulées (null : toutes). */
export type BookingStage = 'upcoming' | 'ongoing' | 'past' | 'cancelled'
export const BOOKING_STAGES: BookingStage[] = ['upcoming', 'ongoing', 'past', 'cancelled']

export type PaymentState = 'awaiting' | 'unpaid' | 'paid' | 'partially_refunded' | 'refunded'

/** Ligne de la liste des réservations. */
export interface BookingListItem {
  reference: string
  apartment: { id: number; name: string | null }
  guest_name: string
  arrival: string
  departure: string
  nights: number
  guests: number
  status: BookingStatus
  source: BookingSource
  total_amount: number
  created_at: string | null
  /** Donné dans la liste seulement. */
  payment_state?: PaymentState | null
}

export interface RefundProposal {
  days_before_arrival: number
  percent: number
  by_policy: number
  paid: number
  amount: number
}

/** Fiche complète d'une réservation. */
export interface BookingDetail extends BookingListItem {
  /** Absent pour certaines réservations saisies à la main. */
  guest_email: string | null
  guest_phone: string | null
  guest_country: string | null
  guest_message: string | null
  locale: string
  adults: number
  children: number
  housekeeping_notes: string | null
  accommodation_amount: number
  cleaning_fee: number
  extras_amount: number
  tourist_tax: number
  discount_amount: number
  price_lines: Array<{ type: string; label: string; amount: number }>
  cancellation_policy: { policy: string; tiers: Array<{ days_before: number; refund_percent: number }> }
  payments: Array<{ id: number; type: 'payment' | 'refund'; status: 'pending' | 'succeeded' | 'failed'; amount: number; created_at: string | null }>
  confirmed_at: string | null
  cancelled_at: string | null
  refund_proposal: RefundProposal | null
}

export interface BookingFilters {
  status?: BookingStatus
  apartment_id?: number
  source?: BookingSource
  from?: string
  to?: string
  search?: string
  stage?: BookingStage
}

export interface BookingListPage {
  data: BookingListItem[]
  meta: { current_page: number; last_page: number; total: number; counts: Record<'all' | BookingStage, number> }
}

export interface ManualBookingInput {
  apartment_id: number
  arrival: string
  departure: string
  adults: number
  children: number
  guest_name: string
  guest_email: string | null
  guest_phone: string | null
  locale: 'fr' | 'en'
  housekeeping_notes: string | null
  /** Total fixé par l'hôte, en centimes ; null pour le prix calculé. */
  total_amount: number | null
  paid: boolean
  send_confirmation: boolean
}

export interface GuestInput {
  guest_name?: string
  guest_email?: string | null
  guest_phone?: string | null
  guest_country?: string | null
  housekeeping_notes?: string | null
}
