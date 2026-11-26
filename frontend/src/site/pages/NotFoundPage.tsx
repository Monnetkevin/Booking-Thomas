import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { buttonClasses } from '@/components/ui/buttonClasses'
import { Container } from '@/components/ui/Container'
import { useLocale } from '@/i18n/useLocale'
import { Seo } from '@/site/components/Seo'

export function NotFoundPage() {
  const { t } = useTranslation()
  const { to } = useLocale()

  return (
    <Container className="flex flex-col items-start gap-4 py-16 sm:py-24">
      <Seo title={t('notFound.title')} description={t('notFound.text')} path="/" noIndex />
      <h1 className="text-3xl font-semibold sm:text-4xl">{t('notFound.title')}</h1>
      <p className="text-muted">{t('notFound.text')}</p>
      <Link to={to('/')} className={buttonClasses('secondary')}>
        {t('notFound.back')}
      </Link>
    </Container>
  )
}
