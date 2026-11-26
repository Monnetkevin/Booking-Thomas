import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { formatLongDate } from '@/lib/dates'
import type { ExtraToPrepare } from '../types'

/** Options des prochaines arrivées, à préparer, une ligne par réservation. */
export function ToPrepareList({ items }: { items: ExtraToPrepare[] }) {
  const { t } = useTranslation()
  if (items.length === 0) return <p className="text-sm text-muted">{t('admin.extras.toPrepareEmpty')}</p>

  return (
    <ul className="divide-y divide-border">
      {items.map((item) => (
        <li key={item.reference} className="flex flex-col gap-0.5 py-3 sm:flex-row sm:gap-4">
          <span className="text-sm font-semibold sm:w-36 sm:shrink-0">{formatLongDate(item.arrival)}</span>
          <span className="text-sm sm:w-40 sm:shrink-0">
            {item.apartment} ·{' '}
            <Link to={`/admin/reservations/${item.reference}`} className="whitespace-nowrap">
              {item.reference}
            </Link>
          </span>
          <span className="text-sm">{item.items.map((line) => `${line.quantity} × ${line.name}`).join(', ')}</span>
        </li>
      ))}
    </ul>
  )
}
