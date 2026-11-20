import '@/admin/i18n'
import { useTranslation } from 'react-i18next'
import { Navigate, useLocation, useNavigate } from 'react-router'
import { loginPath } from '@/admin/auth/redirect'
import { useCurrentUser, useLogout } from '@/admin/auth/useAuth'
import { AdminLayout } from '@/admin/AdminLayout'
import { AccountMenu } from '@/admin/components/AccountMenu'
import { SyncStatus } from '@/admin/components/SyncStatus'
import { canVisit, navigationFor } from '@/admin/navigation'
import { AlertsButton } from '@/admin/notifications/components/AlertsButton'
import { alertParts } from '@/admin/overview/alertSummary'
import { useOverview } from '@/admin/overview/useOverview'
import { ErrorState } from '@/components/feedback/ErrorState'
import { FullPageSpinner } from '@/components/feedback/FullPageSpinner'

/**
 * Garde du back-office : sans session, renvoie vers la connexion en mémorisant la page
 * demandée. L'API reste seule juge des droits (middleware role:) ; ceci n'est qu'un confort.
 */
export function AdminShell() {
  const { t } = useTranslation()
  const location = useLocation()
  const navigate = useNavigate()
  const { data: user, isPending, isError, refetch } = useCurrentUser()
  const logout = useLogout()
  const staff = user?.role === 'admin' || user?.role === 'manager'
  const overview = useOverview(staff)

  if (isPending) return <FullPageSpinner />
  if (isError) return <ErrorState fullPage onRetry={() => void refetch()} />
  if (!user) return <Navigate to={loginPath(location.pathname + location.search)} replace />

  const navigation = navigationFor(user.role)
  // Chacun est ramené à ses pages (le ménage : notifications, puis planning du ménage).
  if (!canVisit(location.pathname, user.role) && navigation[0]) return <Navigate to={navigation[0].to} replace />

  const handleLogout = () =>
    logout.mutate(undefined, { onSettled: () => navigate('/admin/connexion', { replace: true }) })

  const counts = overview.data
  const summary = alertParts(counts)
    .map((part) => t(`admin.alerts.${part.key}`, { count: part.count }))
    .join(' · ')

  return (
    <AdminLayout
      navigation={navigation}
      badges={counts ? { conflicts: counts.conflicts, failingFeeds: counts.failing_feeds, unreadMessages: counts.unread_messages } : {}}
      account={<AccountMenu user={user} items={navigation.filter((item) => item.group === 'account')} onLogout={handleLogout} />}
      alerts={staff ? (collapsed) => <AlertsButton summary={summary || null} collapsed={collapsed} /> : undefined}
      footer={staff && counts ? <SyncStatus lastSyncAt={counts.last_sync_at} failing={counts.failing_feeds > 0} /> : undefined}
    />
  )
}
