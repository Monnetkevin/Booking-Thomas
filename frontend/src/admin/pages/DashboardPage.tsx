import { useTranslation } from 'react-i18next'
import { Link, useSearchParams } from 'react-router'
import { useCurrentUser } from '@/admin/auth/useAuth'
import { MovementList } from '@/admin/dashboard/components/MovementList'
import { OccupancyStrip } from '@/admin/dashboard/components/OccupancyStrip'
import { RevenueChart } from '@/admin/dashboard/components/RevenueChart'
import { TodoList } from '@/admin/dashboard/components/TodoList'
import { KpiCard } from '@/admin/components/KpiCard'
import { countDelta, percentDelta, pointsDelta } from '@/admin/components/delta'
import { movementRows, todoItems } from '@/admin/dashboard/dashboardFigures'
import type { DashboardPeriod } from '@/admin/dashboard/types'
import { useDashboard } from '@/admin/dashboard/useDashboard'
import { addDays } from '@/admin/planning/planningLayout'
import { usePlanning } from '@/admin/planning/usePlanning'
import { ErrorState } from '@/components/feedback/ErrorState'
import { BarChartIcon, CalendarIcon, GridIcon, PlusIcon } from '@/components/icons'
import { buttonClasses } from '@/components/ui/buttonClasses'
import { Section } from '@/components/ui/Section'
import { SegmentedControl } from '@/components/ui/SegmentedControl'
import { Spinner } from '@/components/ui/Spinner'
import { formatDate } from '@/lib/dates'
import { formatEuros } from '@/lib/money'

const percent = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 })
const OCCUPANCY_DAYS = 14

/** Période dans l'adresse (?periode=semaine), pour la garder au rechargement. */
const PERIOD_PARAM: Record<string, DashboardPeriod> = { semaine: 'week', mois: 'month', annee: 'year' }
const PERIOD_SLUG: Record<DashboardPeriod, string> = { week: 'semaine', month: 'mois', year: 'annee' }

/**
 * Tableau de bord : chiffres de la période comparés à la précédente, revenus directs des
 * six derniers mois, situations à traiter, arrivées et départs, occupation des 14 jours.
 */
export function DashboardPage() {
  const { t } = useTranslation()
  const { data: user } = useCurrentUser()
  const [params, setParams] = useSearchParams()
  const period = PERIOD_PARAM[params.get('periode') ?? ''] ?? 'month'
  const dashboard = useDashboard(period)
  const today = dashboard.data?.today
  const planning = usePlanning(today ?? '', OCCUPANCY_DAYS, undefined, Boolean(today))
  const firstName = user?.name.split(' ')[0] ?? ''

  const setPeriod = (value: DashboardPeriod) =>
    setParams((current) => {
      const next = new URLSearchParams(current)
      if (value === 'month') next.delete('periode')
      else next.set('periode', PERIOD_SLUG[value])
      return next
    }, { replace: true })

  return (
    <>
      <header className="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
          <h1 className="text-[28px] leading-tight font-semibold sm:text-[32px]">{t('admin.dashboard.hello', { name: firstName })}</h1>
          {dashboard.data && (
            <p className="mt-1 text-sm text-muted">
              {t(`admin.dashboard.periodIntro.${period}`, {
                from: formatDate(dashboard.data.period.from),
                to: formatDate(addDays(dashboard.data.period.to, -1)),
              })}
            </p>
          )}
        </div>
        <div className="flex flex-wrap items-center gap-3">
          <SegmentedControl
            label={t('admin.dashboard.period')}
            value={period}
            onChange={setPeriod}
            options={(['week', 'month', 'year'] as const).map((value) => ({ value, label: t(`admin.dashboard.periods.${value}`) }))}
          />
          <Link to="/admin/reservations/nouvelle" className={buttonClasses('primary')}>
            <PlusIcon />
            {t('admin.dashboard.newBooking')}
          </Link>
        </div>
      </header>

      {dashboard.isPending ? (
        <Spinner label={t('common.loading')} />
      ) : dashboard.isError ? (
        <ErrorState onRetry={() => void dashboard.refetch()} />
      ) : (
        <div className="space-y-4">
          <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <KpiCard
              icon={<GridIcon />}
              label={t('admin.dashboard.occupancy')}
              value={dashboard.data.figures.occupancy.value === null ? '—' : `${percent.format(dashboard.data.figures.occupancy.value)} %`}
              detail={t(`admin.dashboard.occupancyDetail.${period}`)}
              delta={pointsDelta(dashboard.data.figures.occupancy.value, dashboard.data.figures.occupancy.previous)}
            />
            <KpiCard
              icon={<BarChartIcon />}
              label={t('admin.dashboard.revenue')}
              value={formatEuros(dashboard.data.figures.revenue.value)}
              detail={t('admin.dashboard.revenueDetail')}
              delta={percentDelta(dashboard.data.figures.revenue.value, dashboard.data.figures.revenue.previous)}
            />
            <KpiCard
              icon={<CalendarIcon />}
              label={t('admin.dashboard.stays')}
              value={dashboard.data.figures.stays.value}
              detail={t('admin.dashboard.staysDetail', { count: dashboard.data.figures.stays.direct })}
              delta={countDelta(dashboard.data.figures.stays.value, dashboard.data.figures.stays.previous)}
            />
          </div>

          <div className="grid gap-4 xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)] [&>*]:min-w-0">
            <Section
              title={t('admin.dashboard.revenueChart')}
              description={t('admin.dashboard.revenueChartHint')}
              actions={
                <p className="text-right">
                  <strong className="block text-xl tabular-nums">
                    {formatEuros(dashboard.data.revenue_months.reduce((sum, month) => sum + month.revenue, 0))}
                  </strong>
                </p>
              }
            >
              <RevenueChart months={dashboard.data.revenue_months} />
            </Section>
            <Section
              title={t('admin.dashboard.todoTitle')}
              actions={
                <span className="rounded-full bg-cream px-2.5 py-0.5 text-sm font-bold tabular-nums">{todoItems(dashboard.data.alerts).length}</span>
              }
            >
              <TodoList items={todoItems(dashboard.data.alerts)} />
            </Section>
          </div>

          <div className="grid gap-4 xl:grid-cols-2 [&>*]:min-w-0">
            <Section
              title={t('admin.dashboard.movements')}
              description={t('admin.dashboard.movementsHint')}
              actions={
                <Link to="/admin/planning" className="text-sm font-bold text-accent-strong no-underline">
                  {t('admin.dashboard.seeAll')}
                </Link>
              }
            >
              <MovementList rows={movementRows(dashboard.data.movements)} today={dashboard.data.today} />
            </Section>
            <Section title={t('admin.dashboard.occupancyStrip')} description={t('admin.dashboard.occupancyStripHint', { days: OCCUPANCY_DAYS })}>
              {planning.data ? <OccupancyStrip planning={planning.data} /> : planning.isError ? <ErrorState onRetry={() => void planning.refetch()} /> : <Spinner label={t('common.loading')} />}
            </Section>
          </div>
        </div>
      )}
    </>
  )
}
