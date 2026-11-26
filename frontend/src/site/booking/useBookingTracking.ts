import { useQuery } from '@tanstack/react-query'
import { useLocale } from '@/i18n/useLocale'
import { bookingService, type BookingService } from './bookingService'
import { isAwaitingPayment } from './bookingStatus'

/** Rafraîchissement tant que le paiement est attendu (confirmation par Stripe). */
const PENDING_REFRESH_MS = 30_000

export function useBookingTracking(reference: string, signature: string, service: BookingService = bookingService) {
  const { locale } = useLocale()

  return useQuery({
    queryKey: ['booking', locale, reference, signature],
    queryFn: () => service.track(reference, signature),
    enabled: signature !== '',
    // Un lien invalide (403) ne se corrige pas en réessayant.
    retry: false,
    refetchInterval: (query) => (query.state.data && isAwaitingPayment(query.state.data.status) ? PENDING_REFRESH_MS : false),
  })
}
