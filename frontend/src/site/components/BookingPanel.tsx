import { useMemo } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { QuoteBreakdown } from '@/components/QuoteBreakdown'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Skeleton } from '@/components/ui/Skeleton'
import { Spinner } from '@/components/ui/Spinner'
import { useLocale } from '@/i18n/useLocale'
import { ApiError } from '@/lib/api'
import { formatDate } from '@/lib/dates'
import { formatEuros } from '@/lib/money'
import type { StaySelection } from '@/site/catalog/searchParams'
import { stayCalendarRules } from '@/site/catalog/stayCalendar'
import type { ApartmentDetail } from '@/site/catalog/types'
import { useAvailabilityCalendar, useQuote } from '@/site/catalog/useCatalog'
import { StayCalendar } from './StayCalendar'

interface BookingPanelProps {
  apartment: ApartmentDetail
  stay: StaySelection
  onChange: (stay: StaySelection) => void
}

function Counter({ label, hint, value, min, max, onChange }: { label: string; hint?: string; value: number; min: number; max: number; onChange: (value: number) => void }) {
  return (
    <label className="flex flex-col gap-1 text-xs font-bold tracking-[0.04em] text-muted uppercase">
      {label}
      <select
        className="min-h-11 rounded-[10px] border border-field bg-white px-3 text-[15px] font-medium tracking-normal text-ink normal-case"
        value={value}
        onChange={(event) => onChange(Number(event.target.value))}
      >
        {Array.from({ length: max - min + 1 }, (_, i) => min + i).map((count) => (
          <option key={count} value={count}>
            {count}
          </option>
        ))}
      </select>
      {hint && <span className="font-medium tracking-normal normal-case">{hint}</span>}
    </label>
  )
}

/** Dates, voyageurs, calendrier des disponibilités et prix détaillé calculé par le serveur. */
export function BookingPanel({ apartment, stay, onChange }: BookingPanelProps) {
  const { t } = useTranslation()
  const { locale, to } = useLocale()
  const numberLocale = locale === 'fr' ? 'fr-FR' : 'en-GB'
  const calendar = useAvailabilityCalendar(apartment.slug)
  const rules = useMemo(() => (calendar.data ? stayCalendarRules(calendar.data) : null), [calendar.data])

  const complete = stay.arrival && stay.departure
  const quote = useQuote(
    apartment.slug,
    complete ? { arrival: stay.arrival as string, departure: stay.departure as string, adults: stay.adults, children: stay.children } : null,
  )
  const quoteError = quote.error instanceof ApiError ? (quote.error.serverMessage ?? t('common.error')) : quote.error ? t('common.error') : null

  const dateValue = (date: string | null) => (date ? formatDate(date, numberLocale) : t('booking.choose'))

  return (
    <section aria-labelledby="booking-title" className="flex flex-col gap-5 rounded-[28px] border border-border bg-white p-5 shadow-[0_12px_32px_rgba(43,38,34,0.06)] sm:p-7">
      <h2 id="booking-title" className="sr-only">{t('booking.title')}</h2>
      {apartment.price_from !== null && (
        <p className="flex items-baseline gap-1.5">
          <span className="text-sm text-muted">{t('booking.from')}</span>
          <span className="text-[28px] font-bold">{formatEuros(apartment.price_from, numberLocale)}</span>
          <span className="text-[15px] text-muted">{t('booking.perNight')}</span>
        </p>
      )}

      <dl className="grid grid-cols-2 gap-3">
        {(['arrival', 'departure'] as const).map((field) => (
          <div key={field} className="flex flex-col gap-1 rounded-[10px] border border-field px-3 py-2">
            <dt className="text-xs font-bold tracking-[0.04em] text-muted uppercase">{t(`booking.${field}`)}</dt>
            <dd className="text-[15px] font-medium">{dateValue(stay[field])}</dd>
          </div>
        ))}
      </dl>
      <div className="grid grid-cols-2 gap-3">
        <Counter label={t('booking.adults')} value={stay.adults} min={1} max={Math.max(1, apartment.capacity)} onChange={(adults) => onChange({ ...stay, adults })} />
        <Counter label={t('booking.children')} hint={t('booking.childrenHint')} value={stay.children} min={0} max={Math.max(0, apartment.capacity - 1)} onChange={(children) => onChange({ ...stay, children })} />
      </div>

      <div>
        {calendar.isPending ? (
          <Skeleton className="h-[340px]" />
        ) : calendar.isError || !rules ? (
          <Alert tone="danger">{t('booking.calendarError')}</Alert>
        ) : (
          <>
            <p className="mb-2 text-sm text-muted" aria-live="polite">
              {stay.arrival && !stay.departure
                ? t('booking.pickDeparture', { count: rules.minStayFor(stay.arrival) })
                : !stay.arrival
                  ? t('booking.pickArrival')
                  : null}
            </p>
            <StayCalendar
              calendar={calendar.data}
              rules={rules}
              arrival={stay.arrival}
              departure={stay.departure}
              onChange={(arrival, departure) => onChange({ ...stay, arrival, departure })}
            />
            {stay.arrival && (
              <button
                type="button"
                className="mt-1 min-h-11 text-sm font-semibold text-accent-text underline underline-offset-4"
                onClick={() => onChange({ ...stay, arrival: null, departure: null })}
              >
                {t('booking.clear')}
              </button>
            )}
          </>
        )}
      </div>

      <div aria-live="polite" className="flex flex-col gap-3 border-t border-line pt-4">
        {complete && (quote.isFetching || quote.isPlaceholderData) && (
          <span className="text-muted">
            <Spinner label={t('booking.calculating')} />
          </span>
        )}
        {complete && quoteError && <Alert tone="warning">{quoteError}</Alert>}
        {complete && quote.data && !quoteError && (
          // Ancien prix grisé pendant le recalcul, pour ne jamais le faire passer pour le nouveau.
          <div className={quote.isPlaceholderData ? 'opacity-40' : undefined} aria-busy={quote.isPlaceholderData}>
            <QuoteBreakdown quote={quote.data} locale={numberLocale} />
          </div>
        )}
      </div>

      <Button size="lg" disabled aria-describedby="booking-soon">
        {t('booking.book')}
      </Button>
      <p id="booking-soon" className="text-center text-[13px] leading-relaxed text-muted">
        {t('booking.bookSoon')}{' '}
        <Link to={`${to('/contact')}?logement=${apartment.slug}`}>{t('listing.contactCta')}</Link>
      </p>
    </section>
  )
}
