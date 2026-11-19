export type BookingStatus = 'pending' | 'confirmed' | 'completed' | 'cancelled' | 'expired'
export type BookingSource = 'direct' | 'manual'

export const BOOKING_STATUSES: BookingStatus[] = ['pending', 'confirmed', 'completed', 'cancelled', 'expired']

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
  guest_email: string
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
}

export interface BookingListPage {
  data: BookingListItem[]
  meta: { current_page: number; last_page: number; total: number }
}

export interface GuestInput {
  guest_name?: string
  guest_email?: string
  guest_phone?: string | null
  guest_country?: string | null
  housekeeping_notes?: string | null
}
