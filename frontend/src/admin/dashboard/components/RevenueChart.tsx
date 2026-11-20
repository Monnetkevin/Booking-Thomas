import { useTranslation } from 'react-i18next'
import { StackedBarChart } from '@/admin/components/StackedBarChart'
import { monthLabel } from '@/admin/statistics/chartScale'
import { formatEuros } from '@/lib/money'

/** Revenus directs mois par mois ; le mois en cours est mis en avant. */
export function RevenueChart({ months }: { months: Array<{ month: string; revenue: number }> }) {
  const { t } = useTranslation()

  return (
    <StackedBarChart
      label={t('admin.dashboard.revenueChartLabel', { list: months.map((month) => `${monthLabel(month.month, 'long')} : ${formatEuros(month.revenue)}`).join(', ') })}
      columns={months.map((month, index) => ({
        key: month.month,
        label: monthLabel(month.month),
        title: `${monthLabel(month.month, 'long')} : ${formatEuros(month.revenue)}`,
        caption: month.revenue > 0 ? formatEuros(Math.round(month.revenue / 100) * 100) : undefined,
        segments: [{ value: month.revenue, className: index === months.length - 1 ? 'bg-accent' : 'bg-accent-soft' }],
      }))}
    />
  )
}
