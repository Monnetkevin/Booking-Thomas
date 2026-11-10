import { useTranslation } from 'react-i18next'
import { Navigate, useLocation, useNavigate } from 'react-router'
import { useCurrentUser, useLogout } from '@/admin/auth'
import { AdminLayout } from '@/admin/AdminLayout'
import { LogoutIcon } from '@/components/icons'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Spinner } from '@/components/ui/Spinner'

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

  if (isPending) {
    return (
      <div className="grid min-h-dvh place-items-center bg-sand text-muted">
        <Spinner label={t('common.loading')} />
      </div>
    )
  }

  if (isError) {
    return (
      <div className="grid min-h-dvh place-items-center bg-sand px-4">
        <div className="flex flex-col items-start gap-3">
          <Alert tone="danger">{t('common.error')}</Alert>
          <Button variant="secondary" onClick={() => void refetch()}>
            {t('common.retry')}
          </Button>
        </div>
      </div>
    )
  }

  if (!user) {
    const redirect = encodeURIComponent(location.pathname + location.search)
    return <Navigate to={`/admin/connexion?redirect=${redirect}`} replace />
  }

  const footer = (
    <div className="flex items-center justify-between gap-2">
      <span className="truncate px-1 text-[13px] text-night-text">
        {t('admin.connectedAs', { name: user.name })}
      </span>
      <button
        type="button"
        onClick={() => logout.mutate(undefined, { onSettled: () => navigate('/admin/connexion', { replace: true }) })}
        aria-label={t('admin.logout')}
        title={t('admin.logout')}
        className="flex size-11 shrink-0 items-center justify-center rounded-[10px] text-night-text hover:bg-night-soft hover:text-white"
      >
        <LogoutIcon />
      </button>
    </div>
  )

  return <AdminLayout footer={footer} />
}
