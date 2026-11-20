import { useTranslation } from 'react-i18next'
import { StackedBarChart } from '@/admin/components/StackedBarChart'
import { formatEuros } from '@/lib/money'
import { CHANNEL_CLASSES } from '../channels'
import { monthLabel } from '../chartScale'
import type { MonthStats } from '../types'

/**
 * Revenus par mois, empilés : direct (bas), Airbnb, Booking.com. Un mois dont les revenus
 * des plateformes ne sont pas importés est marqué d'un astérisque.
 */
export function RevenueByMonthChart({ months }: { months: MonthStats[] }) {
  const { t } = useTranslation()
  const total = (month: MonthStats) => month.revenue + month.platform_revenue.airbnb + month.platform_revenue.booking

  return (
    <StackedBarChart
      heightClass="h-52"
      label={t('admin.statistics.revenueChartLabel', { list: months.map((month) => `${monthLabel(month.month, 'long')} : ${formatEuros(total(month))}`).join(', ') })}
      columns={months.map((month) => ({
        key: month.month,
        label: `${monthLabel(month.month)}${month.platform_revenue_missing ? '*' : ''}`,
        title: `${monthLabel(month.month, 'long')} : ${formatEuros(total(month))}`,
        segments: [
          { value: month.revenue, className: CHANNEL_CLASSES.direct },
          { value: month.platform_revenue.airbnb, className: CHANNEL_CLASSES.airbnb },
          { value: month.platform_revenue.booking, className: CHANNEL_CLASSES.booking },
        ],
      }))}
    />
  )
}
