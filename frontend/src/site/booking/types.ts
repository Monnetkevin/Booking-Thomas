import type { Quote } from '@/lib/pricing/types'
import type { CancellationTerms } from '@/site/components/CancellationTiers'
import type { PublicPhoto } from '@/site/catalog/types'

export type BookingStatus = 'pending' | 'confirmed' | 'completed' | 'cancelled' | 'expired'

/** Réservation vue par le voyageur depuis son lien signé (GET /api/bookings/{référence}). */
export interface BookingTracking {
  reference: string
  status: BookingStatus
  created_at: string
  /** Fin de la retenue des nuits, tant que le paiement est attendu. */
  expires_at: string | null
  confirmed_at: string | null
  guest: { name: string; email: string }
  stay: { arrival: string; departure: string; nights: number; adults: number; children: number }
  apartment: {
    slug: string
    name: string
    cover: PublicPhoto | null
    check_in_time: string
    check_out_time: string
    /** Rue sans numéro tant que la réservation n'est pas confirmée. */
    address: string
    postal_code: string
    city: string
  }
  price: Quote
  cancellation: CancellationTerms
}
