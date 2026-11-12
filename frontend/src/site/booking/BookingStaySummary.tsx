import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { useLocale } from '@/i18n/useLocale'
import { formatLongDate } from '@/lib/dates'
import { Picture } from '@/site/components/Picture'
import type { BookingTracking } from './types'

function Row({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div className="flex flex-col gap-0.5 border-t border-line py-3 sm:flex-row sm:justify-between sm:gap-6">
      <dt className="text-muted">{label}</dt>
      <dd className="font-medium sm:text-right">{children}</dd>
    </div>
  )
}

/** Logement, dates avec horaires, voyageurs et adresse d'une réservation. */
export function BookingStaySummary({ booking }: { booking: BookingTracking }) {
  const { t } = useTranslation()
  const { locale, to } = useLocale()
  const dateLocale = locale === 'fr' ? 'fr-FR' : 'en-GB'
  const { apartment, stay } = booking
  const confirmed = booking.status === 'confirmed' || booking.status === 'completed'
  const guests = [t('tracking.adults', { count: stay.adults }), stay.children > 0 ? t('tracking.children', { count: stay.children }) : null]
    .filter(Boolean)
    .join(', ')

  return (
    <section aria-labelledby="sejour-titre" className="overflow-hidden rounded-[var(--radius-card)] border border-line bg-white">
      {apartment.cover && (
        <div className="h-48 overflow-hidden bg-cream sm:h-72">
          <Picture photo={apartment.cover} sizes="(min-width: 768px) 720px, 100vw" alt="" />
        </div>
      )}
      <div className="flex flex-col gap-2 p-5 sm:p-7">
        <p className="text-[13px] font-bold tracking-[0.08em] text-accent-text uppercase" id="sejour-titre">
          {t('tracking.stayTitle')}
        </p>
        <h2 className="text-2xl sm:text-[28px]">
          <Link to={to(`/logements/${apartment.slug}`)} className="text-ink no-underline hover:text-accent-text">
            {apartment.name}
          </Link>
        </h2>
        <dl className="mt-2 text-[15px]">
          <Row label={t('tracking.arrival')}>
            {t('tracking.arrivalValue', { date: formatLongDate(stay.arrival, dateLocale), time: apartment.check_in_time })}
          </Row>
          <Row label={t('tracking.departure')}>
            {t('tracking.departureValue', { date: formatLongDate(stay.departure, dateLocale), time: apartment.check_out_time })}
          </Row>
          <Row label={t('tracking.guests')}>{guests}</Row>
          <Row label={t('tracking.bookedBy')}>{booking.guest.name}</Row>
          <Row label={t('tracking.address')}>
            {apartment.address}, {apartment.postal_code} {apartment.city}
            {!confirmed && <span className="mt-1 block text-sm font-normal text-muted">{t('tracking.addressPending')}</span>}
          </Row>
        </dl>
      </div>
    </section>
  )
}
