import { useTranslation } from 'react-i18next'
import { Alert } from '@/components/ui/Alert'
import { useLocale } from '@/i18n/useLocale'
import { formatTime } from '@/lib/dates'
import { bookingStatusView } from './bookingStatus'
import type { BookingTracking } from './types'

/** Titre et message selon le statut (heure limite de paiement si la réservation est en attente). */
export function BookingStatusBanner({ booking }: { booking: BookingTracking }) {
  const { t } = useTranslation()
  const { locale } = useLocale()
  const view = bookingStatusView(booking.status)
  const time = booking.expires_at ? formatTime(booking.expires_at, locale === 'fr' ? 'fr-FR' : 'en-GB') : ''

  return (
    <div className="flex flex-col gap-4">
      <h1 className="text-[34px] leading-[1.08] sm:text-[44px]">{t(`${view.key}.title`)}</h1>
      <Alert tone={view.tone}>{t(`${view.key}.text`, { time })}</Alert>
    </div>
  )
}
