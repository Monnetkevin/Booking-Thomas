import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { useCurrentUser } from '@/admin/auth/useAuth'
import { PageHeader } from '@/admin/components/PageHeader'
import { StatTile } from '@/admin/dashboard/components/StatTile'
import type { Movement } from '@/admin/dashboard/types'
import { useDashboard } from '@/admin/dashboard/useDashboard'
import { ErrorState } from '@/components/feedback/ErrorState'
import { Alert } from '@/components/ui/Alert'
import { Section } from '@/components/ui/Section'
import { Spinner } from '@/components/ui/Spinner'
import { formatDate, formatLongDate } from '@/lib/dates'
import { formatEuros } from '@/lib/money'

const percent = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 })

function MovementLine({ movement }: { movement: Movement }) {
  const { t } = useTranslation()
  const text = (
    <>
      <span className="font-semibold">{movement.apartment}</span> · {movement.label ?? t('admin.dashboard.platform')}
      {movement.guests !== null && ` · ${t('admin.dashboard.guests', { count: movement.guests })}`}
    </>
  )
  return <li>{movement.reference ? <Link to={`/admin/reservations/${movement.reference}`}>{text}</Link> : text}</li>
}

/** Tableau de bord : alertes, chiffres du mois, arrivées et départs des 7 prochains jours. */
export function DashboardPage() {
  const { t } = useTranslation()
  const { data: user } = useCurrentUser()
  const dashboard = useDashboard()

  return (
    <>
      <PageHeader title={t('admin.dashboard.title')} subtitle={t('admin.dashboard.welcome', { name: user?.name ?? '' })} />
      {dashboard.isPending ? (
        <Spinner label={t('common.loading')} />
      ) : dashboard.isError ? (
        <ErrorState onRetry={() => void dashboard.refetch()} />
      ) : (
        <div className="space-y-5">
          {dashboard.data.alerts.conflicts.length > 0 && (
            <Alert tone="danger">
              <span className="font-semibold">{t('admin.dashboard.conflicts', { count: dashboard.data.alerts.conflicts.length })}</span>{' '}
              {dashboard.data.alerts.conflicts.map((conflict) => `${conflict.apartment} (${formatDate(conflict.starts_on)} → ${formatDate(conflict.ends_on)})`).join(' · ')}{' '}
              <Link to="/admin/planning">{t('admin.dashboard.openPlanning')}</Link>
            </Alert>
          )}
          {dashboard.data.alerts.failing_feeds.map((feed) => (
            <Alert key={`${feed.apartment}-${feed.provider}`} tone="warning">
              {feed.empty_refused
                ? t('admin.dashboard.emptyRefused', { provider: feed.provider, apartment: feed.apartment })
                : t('admin.dashboard.failingFeed', { provider: feed.provider, apartment: feed.apartment, count: feed.failures })}{' '}
              <Link to="/admin/synchronisation">{t('admin.dashboard.openSync')}</Link>
            </Alert>
          ))}
          {dashboard.data.alerts.unread_messages > 0 && (
            <Alert tone="info">
              {t('admin.dashboard.unreadMessages', { count: dashboard.data.alerts.unread_messages })}{' '}
              <Link to="/admin/messages">{t('admin.dashboard.openMessages')}</Link>
            </Alert>
          )}

          <div className="grid gap-4 sm:grid-cols-3">
            <StatTile
              label={t('admin.dashboard.occupancy')}
              value={dashboard.data.month.occupancy === null ? '—' : `${percent.format(dashboard.data.month.occupancy)} %`}
              detail={t('admin.dashboard.occupancyDetail')}
            />
            <StatTile
              label={t('admin.dashboard.nightsSold')}
              value={dashboard.data.month.nights.direct + dashboard.data.month.nights.platform}
              detail={t('admin.dashboard.nightsDetail', { direct: dashboard.data.month.nights.direct, platform: dashboard.data.month.nights.platform })}
            />
            <StatTile label={t('admin.dashboard.revenue')} value={formatEuros(dashboard.data.month.revenue)} detail={t('admin.dashboard.revenueDetail')} />
          </div>

          <Section title={t('admin.dashboard.movements')}>
            <ol className="divide-y divide-line">
              {dashboard.data.movements.map((day) => (
                <li key={day.date} className="grid gap-2 py-3 sm:grid-cols-[12rem_1fr_1fr]">
                  <p className="font-semibold">{day.date === dashboard.data.today ? t('admin.dashboard.today') : formatLongDate(day.date)}</p>
                  <div>
                    <p className="text-sm text-muted">{t('admin.dashboard.arrivals')}</p>
                    {day.arrivals.length === 0 ? <p className="text-sm text-muted">—</p> : <ul className="space-y-0.5 text-[15px]">{day.arrivals.map((m, i) => <MovementLine key={i} movement={m} />)}</ul>}
                  </div>
                  <div>
                    <p className="text-sm text-muted">{t('admin.dashboard.departures')}</p>
                    {day.departures.length === 0 ? <p className="text-sm text-muted">—</p> : <ul className="space-y-0.5 text-[15px]">{day.departures.map((m, i) => <MovementLine key={i} movement={m} />)}</ul>}
                  </div>
                </li>
              ))}
            </ol>
          </Section>
        </div>
      )}
    </>
  )
}
