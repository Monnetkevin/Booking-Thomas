import { useTranslation } from 'react-i18next'
import { formatEuros } from '@/lib/money'
import { monthLabel } from '../chartScale'
import { CHANNEL_CLASSES } from '../channels'
import type { MonthStats } from '../types'

/**
 * Revenus par mois, empilés : direct (bas), Airbnb, Booking.com. Un mois dont les revenus
 * des plateformes ne sont pas importés est marqué d'un astérisque.
 */
export function RevenueByMonthChart({ months }: { months: MonthStats[] }) {
  const { t } = useTranslation()
  const total = (month: MonthStats) => month.revenue + month.platform_revenue.airbnb + month.platform_revenue.booking
  const max = Math.max(1, ...months.map(total))
  const description = months.map((month) => `${monthLabel(month.month, 'long')} : ${formatEuros(total(month))}`).join(', ')

  return (
    <figure>
      <div role="img" aria-label={t('admin.statistics.revenueChartLabel', { list: description })} className="flex h-52 items-end gap-1.5 sm:gap-2.5">
        {months.map((month) => (
          <div key={month.month} className="flex h-full min-w-0 flex-1 flex-col justify-end" title={`${monthLabel(month.month, 'long')} : ${formatEuros(total(month))}`}>
            {(['booking', 'airbnb'] as const).map((key) =>
              month.platform_revenue[key] > 0 ? (
                <div key={key} className={CHANNEL_CLASSES[key]} style={{ height: `${(month.platform_revenue[key] / max) * 100}%` }} />
              ) : null,
            )}
            <div className={`${CHANNEL_CLASSES.direct} rounded-b-sm`} style={{ height: `${Math.max(month.revenue > 0 ? 1 : 0, (month.revenue / max) * 100)}%` }} />
          </div>
        ))}
      </div>
      <div aria-hidden="true" className="mt-2 flex gap-1.5 border-t border-line pt-2 sm:gap-2.5">
        {months.map((month) => (
          <span key={month.month} className="min-w-0 flex-1 truncate text-center text-[11px] text-muted capitalize">
            {monthLabel(month.month)}
            {month.platform_revenue_missing && '*'}
          </span>
        ))}
      </div>
    </figure>
  )
}
