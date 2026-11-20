import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { PageHeader } from '@/admin/components/PageHeader'
import { buttonClasses } from '@/components/ui/buttonClasses'

/** Adresse inconnue sous /admin : on reste dans le back-office. */
export function AdminNotFoundPage() {
  const { t } = useTranslation()

  return (
    <>
      <PageHeader title={t('admin.notFound.title')} subtitle={t('admin.notFound.text')} />
      <Link to="/admin" className={buttonClasses('secondary')}>
        {t('admin.notFound.back')}
      </Link>
    </>
  )
}
