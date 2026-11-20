import { useTranslation } from 'react-i18next'
import { PageHeader } from '@/admin/components/PageHeader'
import { StatTile } from '@/admin/dashboard/components/StatTile'
import { ErrorState } from '@/components/feedback/ErrorState'
import { DownloadIcon } from '@/components/icons'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Section } from '@/components/ui/Section'
import { Spinner } from '@/components/ui/Spinner'
import { formatDate } from '@/lib/dates'
import { formatEuros } from '@/lib/money'
import { monthLabel } from '../chartScale'
import { NightsChart } from '../components/NightsChart'
import { useDownloadStatisticsCsv, useStatistics } from '../useStatistics'

const decimal = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 })
const rate = (value: number | null) => (value === null ? '—' : `${decimal.format(value)} %`)

/** Statistiques des 12 derniers mois : indicateurs, nuits par mois, détail par logement, export CSV. */
export function StatisticsPage() {
  const { t } = useTranslation()
  const statistics = useStatistics()
  const exportCsv = useDownloadStatisticsCsv()

  if (statistics.isPending) return <Spinner label={t('common.loading')} />
  if (statistics.isError) return <ErrorState onRetry={() => void statistics.refetch()} />

  const data = statistics.data
  const totals = data.totals
  const cell = 'px-3 py-2 text-right tabular-nums'

  return (
    <>
      <PageHeader
        title={t('admin.statistics.title')}
        subtitle={t('admin.statistics.subtitle', { from: formatDate(data.from), to: formatDate(data.to) })}
        actions={
          <Button variant="secondary" loading={exportCsv.isPending} onClick={() => exportCsv.mutate()}>
            <DownloadIcon />
            {t('admin.statistics.export')}
          </Button>
        }
      />
      {exportCsv.isError && (
        <div className="mb-4">
          <Alert tone="danger">{t('common.error')}</Alert>
        </div>
      )}
      <div className="space-y-5">
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <StatTile label={t('admin.statistics.revenue')} value={formatEuros(totals.revenue)} detail={t('admin.statistics.revenueDetail')} />
          <StatTile
            label={t('admin.statistics.nights')}
            value={totals.nights_direct + totals.nights_platform}
            detail={t('admin.dashboard.nightsDetail', { direct: totals.nights_direct, platform: totals.nights_platform })}
          />
          <StatTile label={t('admin.statistics.occupancy')} value={rate(totals.occupancy)} detail={t('admin.statistics.occupancyDetail')} />
          <StatTile
            label={t('admin.statistics.averagePrice')}
            value={totals.average_nightly_price === null ? '—' : formatEuros(totals.average_nightly_price)}
            detail={t('admin.statistics.averagePriceDetail')}
          />
          <StatTile
            label={t('admin.statistics.averageStay')}
            value={totals.average_stay === null ? '—' : t('admin.statistics.nightsValue', { value: decimal.format(totals.average_stay) })}
            detail={t('admin.statistics.averageStayDetail')}
          />
        </div>

        <Section title={t('admin.statistics.chartTitle')} description={t('admin.statistics.chartHint')}>
          <NightsChart months={data.months} />
          <div className="mt-4 overflow-x-auto">
            <table className="w-full min-w-[32rem] text-sm">
              <caption className="sr-only">{t('admin.statistics.monthsTable')}</caption>
              <thead className="text-muted">
                <tr>
                  <th scope="col" className="px-3 py-2 text-left font-semibold">{t('admin.statistics.month')}</th>
                  <th scope="col" className={cell}>{t('admin.statistics.direct')}</th>
                  <th scope="col" className={cell}>{t('admin.statistics.platform')}</th>
                  <th scope="col" className={cell}>{t('admin.statistics.revenueShort')}</th>
                  <th scope="col" className={cell}>{t('admin.statistics.occupancyShort')}</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-line">
                {[...data.months].reverse().map((month) => (
                  <tr key={month.month}>
                    <th scope="row" className="px-3 py-2 text-left font-medium capitalize">{monthLabel(month.month, 'long')}</th>
                    <td className={cell}>{month.nights_direct}</td>
                    <td className={cell}>{month.nights_platform}</td>
                    <td className={cell}>{formatEuros(month.revenue)}</td>
                    <td className={cell}>{rate(month.occupancy)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Section>

        <Section title={t('admin.statistics.byApartment')} description={t('admin.statistics.platformAmounts')}>
          <div className="overflow-x-auto">
            <table className="w-full min-w-[36rem] text-sm">
              <thead className="text-muted">
                <tr>
                  <th scope="col" className="px-3 py-2 text-left font-semibold">{t('admin.planning.apartment')}</th>
                  <th scope="col" className={cell}>{t('admin.statistics.occupancyShort')}</th>
                  <th scope="col" className={cell}>{t('admin.statistics.direct')}</th>
                  <th scope="col" className={cell}>{t('admin.statistics.platform')}</th>
                  <th scope="col" className={cell}>{t('admin.statistics.revenueShort')}</th>
                  <th scope="col" className={cell}>{t('admin.statistics.rating')}</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-line">
                {data.apartments.map((apartment) => (
                  <tr key={apartment.id}>
                    <th scope="row" className="px-3 py-2 text-left font-medium">{apartment.name}</th>
                    <td className={cell}>{rate(apartment.occupancy)}</td>
                    <td className={cell}>{apartment.nights_direct}</td>
                    <td className={cell}>{apartment.nights_platform}</td>
                    <td className={cell}>{formatEuros(apartment.revenue)}</td>
                    <td className={cell}>{apartment.rating === null ? '—' : decimal.format(apartment.rating)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Section>
      </div>
    </>
  )
}
