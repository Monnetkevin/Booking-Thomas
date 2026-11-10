import { Navigate, useLocation, useNavigate } from 'react-router'
import { loginPath } from '@/admin/auth/redirect'
import { useCurrentUser, useLogout } from '@/admin/auth/useAuth'
import { AdminLayout } from '@/admin/AdminLayout'
import { UserMenu } from '@/admin/components/UserMenu'
import { navigationFor } from '@/admin/navigation'
import { ErrorState } from '@/components/feedback/ErrorState'
import { FullPageSpinner } from '@/components/feedback/FullPageSpinner'

/**
 * Garde du back-office : sans session, renvoie vers la connexion en mémorisant la page
 * demandée. L'API reste seule juge des droits (middleware role:) ; ceci n'est qu'un confort.
 */
export function AdminShell() {
  const location = useLocation()
  const navigate = useNavigate()
  const { data: user, isPending, isError, refetch } = useCurrentUser()
  const logout = useLogout()

  if (isPending) return <FullPageSpinner />
  if (isError) return <ErrorState fullPage onRetry={() => void refetch()} />
  if (!user) return <Navigate to={loginPath(location.pathname + location.search)} replace />

  const handleLogout = () =>
    logout.mutate(undefined, { onSettled: () => navigate('/admin/connexion', { replace: true }) })

  return <AdminLayout navigation={navigationFor(user.role)} footer={<UserMenu user={user} onLogout={handleLogout} />} />
}
