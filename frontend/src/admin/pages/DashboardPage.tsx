import { useTranslation } from 'react-i18next'
import { useCurrentUser } from '@/admin/auth/useAuth'
import { PageHeader } from '@/admin/components/PageHeader'

export function DashboardPage() {
  const { t } = useTranslation()
  const { data: user } = useCurrentUser()

  return (
    <>
      <PageHeader title={t('admin.dashboard.title')} />
      <p className="text-muted">{t('admin.dashboard.welcome', { name: user?.name ?? '' })}</p>
    </>
  )
}
