import { useTranslation } from 'react-i18next'
import { monthLabel } from '@/admin/statistics/chartScale'
import { formatEuros } from '@/lib/money'

/** Barres des revenus directs mois par mois ; le mois en cours est mis en avant. */
export function RevenueChart({ months }: { months: Array<{ month: string; revenue: number }> }) {
  const { t } = useTranslation()
  const max = Math.max(1, ...months.map((month) => month.revenue))
  const description = months.map((month) => `${monthLabel(month.month, 'long')} : ${formatEuros(month.revenue)}`).join(', ')

  return (
    <figure>
      <div role="img" aria-label={t('admin.dashboard.revenueChartLabel', { list: description })} className="flex h-44 items-end gap-3">
        {months.map((month, index) => {
          const current = index === months.length - 1
          return (
            <div key={month.month} className="flex h-full flex-1 flex-col items-center justify-end gap-1.5">
              <span className="text-[11px] font-semibold text-muted tabular-nums">{month.revenue > 0 ? formatEuros(Math.round(month.revenue / 100) * 100) : ''}</span>
              <div
                className={current ? 'w-full max-w-12 rounded-t-lg bg-accent' : 'w-full max-w-12 rounded-t-lg bg-accent-soft'}
                style={{ height: `${Math.max(2, (month.revenue / max) * 100)}%` }}
              />
            </div>
          )
        })}
      </div>
      <div aria-hidden="true" className="mt-2 flex gap-3 border-t border-line pt-2">
        {months.map((month) => (
          <span key={month.month} className="flex-1 text-center text-xs text-muted capitalize">
            {monthLabel(month.month)}
          </span>
        ))}
      </div>
    </figure>
  )
}
