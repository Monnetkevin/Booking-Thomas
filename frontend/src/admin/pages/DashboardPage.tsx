import { useTranslation } from 'react-i18next'
import { PageHeader } from '@/admin/components/PageHeader'

export function DashboardPage() {
  const { t } = useTranslation()

  return <PageHeader title={t('admin.dashboard.title')} />
}
