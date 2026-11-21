import { useTranslation } from 'react-i18next'
import { cn } from '@/lib/cn'
import { formatDateTime, formatLongDate } from '@/lib/dates'
import { formatEuros } from '@/lib/money'
import type { BookingHistoryEvent } from '../types'

/** Frise de la réservation : ce qui s'est passé, puis l'arrivée et le départ à venir. */
export function BookingHistoryList({ events }: { events: BookingHistoryEvent[] }) {
  const { t } = useTranslation()

  return (
    <ol className="relative space-y-4 border-l-2 border-line pl-5">
      {events.map((event, index) => {
        const stayDate = event.event === 'arrival' || event.event === 'departure'
        return (
          <li key={`${event.event}-${index}`} className="relative">
            <span
              aria-hidden="true"
              className={cn(
                'absolute top-1 -left-[27px] size-3 rounded-full ring-4 ring-white',
                event.upcoming ? 'bg-white ring-offset-0 outline-2 outline-field' : event.event === 'cancelled' || event.event === 'payment_failed' ? 'bg-danger' : 'bg-accent',
              )}
            />
            <p className={cn('text-sm font-semibold', event.upcoming && 'text-muted')}>
              {t(`admin.bookings.events.${event.event}`)}
              {event.amount !== null && ` · ${formatEuros(event.amount)}`}
            </p>
            <p className="text-xs text-muted first-letter:uppercase">
              {stayDate ? formatLongDate(event.at.slice(0, 10)) : formatDateTime(event.at)}
              {event.by && ` · ${t('admin.bookings.eventBy', { name: event.by })}`}
            </p>
          </li>
        )
      })}
    </ol>
  )
}
