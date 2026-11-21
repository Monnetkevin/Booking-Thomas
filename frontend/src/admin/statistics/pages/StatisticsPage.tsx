import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { useCurrentUser } from '@/admin/auth/useAuth'
import { FEATURES } from '@/admin/features'
import { canVisit } from '@/admin/navigation'
import { KpiCard } from '@/admin/components/KpiCard'
import { PageHeader } from '@/admin/components/PageHeader'
import { ErrorState } from '@/components/feedback/ErrorState'
import { BarChartIcon, CalendarIcon, DownloadIcon, GridIcon, HomeIcon } from '@/components/icons'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Section } from '@/components/ui/Section'
import { SegmentedControl } from '@/components/ui/SegmentedControl'
import { Spinner } from '@/components/ui/Spinner'
import { formatDate } from '@/lib/dates'
import { formatEuros } from '@/lib/money'
import { addDays } from '@/admin/planning/planningLayout'
import { CHANNEL_CLASSES, channelShares } from '../channels'
import { monthLabel } from '../chartScale'
import { NightsChart } from '../components/NightsChart'
import { RevenueByMonthChart } from '../components/RevenueByMonthChart'
import type { StatisticsPeriod } from '../types'
import { useDownloadStatisticsCsv, useStatistics } from '../useStatistics'

const decimal = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 })
const rate = (value: number | null) => (value === null ? '—' : `${decimal.format(value)} %`)
const PERIODS: StatisticsPeriod[] = ['six_months', 'year', 'all']

/**
 * Statistiques sur 6 mois, 12 mois ou depuis le début : revenus par canal (direct et
 * plateformes importées), part du direct, revenu par nuit, durée moyenne, occupation,
 * détail par mois et par logement, export CSV.
 */
export function StatisticsPage() {
  const { t } = useTranslation()
  const { data: user } = useCurrentUser()
  const [period, setPeriod] = useState<StatisticsPeriod>('year')
  const statistics = useStatistics(period)
  const exportCsv = useDownloadStatisticsCsv()

  if (statistics.isPending) return <Spinner label={t('common.loading')} />
  if (statistics.isError) return <ErrorState onRetry={() => void statistics.refetch()} />

  const data = statistics.data
  const totals = data.totals
  const cell = 'px-3 py-2 text-right tabular-nums'
  const missing = data.months.some((month) => month.platform_revenue_missing)
  const channels = channelShares(totals)
  const maxOccupancy = Math.max(1, ...data.apartments.map((apartment) => apartment.occupancy ?? 0))

  return (
    <>
      <PageHeader
        title={t('admin.statistics.title')}
        subtitle={t('admin.statistics.subtitle', { from: formatDate(data.from), to: formatDate(addDays(data.to, -1)) })}
        actions={
          <>
            <SegmentedControl
              label={t('admin.statistics.period')}
              value={period}
              onChange={setPeriod}
              options={PERIODS.map((value) => ({ value, label: t(`admin.statistics.periods.${value}`) }))}
            />
            <Button variant="secondary" loading={exportCsv.isPending} onClick={() => exportCsv.mutate(period)}>
              <DownloadIcon />
              {t('admin.statistics.export')}
            </Button>
          </>
        }
      />
      {exportCsv.isError && (
        <div className="mb-4">
          <Alert tone="danger">{t('common.error')}</Alert>
        </div>
      )}
      {missing && (
        <div className="mb-4">
          <Alert tone="warning">
            {FEATURES.platformImport ? (
              <>
                {t('admin.statistics.platformMissing')}{' '}
                {user && canVisit('/admin/revenus-plateformes', user.role) && <Link to="/admin/revenus-plateformes">{t('admin.statistics.importLink')}</Link>}
              </>
            ) : (
              t('admin.statistics.platformNotTracked')
            )}
          </Alert>
        </div>
      )}
      <div className="space-y-5">
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <KpiCard icon={<BarChartIcon />} label={t('admin.statistics.totalRevenue')} value={formatEuros(totals.total_revenue)} detail={t('admin.statistics.totalRevenueDetail')} delta={null} />
          <KpiCard
            icon={<HomeIcon />}
            label={t('admin.statistics.revenuePerNight')}
            value={totals.revenue_per_night === null ? '—' : formatEuros(totals.revenue_per_night)}
            detail={t('admin.statistics.revenuePerNightDetail', { count: totals.nights_direct + totals.nights_platform })}
            delta={null}
          />
          <KpiCard
            icon={<CalendarIcon />}
            label={t('admin.statistics.averageStay')}
            value={totals.average_stay === null ? '—' : t('admin.statistics.nightsValue', { value: decimal.format(totals.average_stay) })}
            detail={t('admin.statistics.averageStayDetail')}
            delta={null}
          />
          <KpiCard icon={<GridIcon />} label={t('admin.statistics.directShare')} value={rate(totals.direct_share)} detail={t('admin.statistics.occupancyLine', { value: rate(totals.occupancy) })} delta={null} />
        </div>

        <div className="grid gap-5 xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)] [&>*]:min-w-0">
          <Section title={t('admin.statistics.revenueChart')} description={t('admin.statistics.revenueChartHint')}>
            <RevenueByMonthChart months={data.months} />
            <ul className="mt-3 flex flex-wrap gap-4 text-xs text-muted">
              {(['direct', 'airbnb', 'booking'] as const).map((key) => (
                <li key={key} className="flex items-center gap-1.5">
                  <span aria-hidden="true" className={`size-3 rounded ${CHANNEL_CLASSES[key]}`} />
                  {t(`admin.statistics.channels.${key}`)}
                </li>
              ))}
              {missing && <li>{t('admin.statistics.missingMark')}</li>}
            </ul>
          </Section>
          <Section title={t('admin.statistics.channelsTitle')}>
            {channels.length === 0 ? (
              <p className="text-sm text-muted">{t('admin.statistics.noRevenue')}</p>
            ) : (
              <ul className="space-y-4">
                {channels.map((channel) => (
                  <li key={channel.key}>
                    <p className="flex justify-between gap-2 text-sm">
                      <span className="font-semibold">{t(`admin.statistics.channels.${channel.key}`)}</span>
                      <span className="tabular-nums">
                        {formatEuros(channel.amount)} · {decimal.format(channel.share)} %
                      </span>
                    </p>
                    <div className="mt-1.5 h-2.5 rounded-full bg-cream">
                      <div className={`h-full rounded-full ${CHANNEL_CLASSES[channel.key]}`} style={{ width: `${channel.share}%` }} />
                    </div>
                  </li>
                ))}
              </ul>
            )}
            <h3 className="mt-6 mb-3 font-sans text-sm font-bold tracking-normal">{t('admin.statistics.occupancyByApartment')}</h3>
            <ul className="space-y-3">
              {data.apartments.map((apartment) => (
                <li key={apartment.id}>
                  <p className="flex justify-between gap-2 text-sm">
                    <span>{apartment.name}</span>
                    <span className="font-semibold tabular-nums">{rate(apartment.occupancy)}</span>
                  </p>
                  <div className="mt-1 h-2 rounded-full bg-cream">
                    <div className="h-full rounded-full bg-accent" style={{ width: `${((apartment.occupancy ?? 0) / maxOccupancy) * 100}%` }} />
                  </div>
                </li>
              ))}
            </ul>
          </Section>
        </div>

        <Section title={t('admin.statistics.chartTitle')} description={t('admin.statistics.chartHint')}>
          <NightsChart months={data.months} />
          <div className="relative mt-4 overflow-x-auto">
            <table className="w-full min-w-[32rem] text-sm">
              <caption className="sr-only">{t('admin.statistics.monthsTable')}</caption>
              <thead className="text-muted">
                <tr>
                  <th scope="col" className="px-3 py-2 text-left font-semibold">{t('admin.statistics.month')}</th>
                  <th scope="col" className={cell}>{t('admin.statistics.direct')}</th>
                  <th scope="col" className={cell}>{t('admin.statistics.platform')}</th>
                  <th scope="col" className={cell}>{t('admin.statistics.revenueShort')}</th>
                  <th scope="col" className={cell}>{t('admin.statistics.platformRevenueShort')}</th>
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
                    <td className={cell}>{month.platform_revenue_missing ? '—' : formatEuros(month.platform_revenue.airbnb + month.platform_revenue.booking)}</td>
                    <td className={cell}>{rate(month.occupancy)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Section>

        <Section title={t('admin.statistics.byApartment')} description={t('admin.statistics.platformAmounts')}>
          <div className="relative overflow-x-auto">
            <table className="w-full min-w-[36rem] text-sm">
              <thead className="text-muted">
                <tr>
                  <th scope="col" className="px-3 py-2 text-left font-semibold">{t('admin.planning.apartment')}</th>
                  <th scope="col" className={cell}>{t('admin.statistics.occupancyShort')}</th>
                  <th scope="col" className={cell}>{t('admin.statistics.direct')}</th>
                  <th scope="col" className={cell}>{t('admin.statistics.platform')}</th>
                  <th scope="col" className={cell}>{t('admin.statistics.revenueShort')}</th>
                  <th scope="col" className={cell}>{t('admin.statistics.platformRevenueShort')}</th>
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
                    <td className={cell}>{formatEuros(apartment.platform_revenue)}</td>
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
