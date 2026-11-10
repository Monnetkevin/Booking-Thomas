import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { useLocale } from './useLocale'

export function LanguageSwitcher({ className }: { className?: string }) {
  const { t } = useTranslation()
  const { locale, switchTo } = useLocale()
  const target = locale === 'fr' ? 'en' : 'fr'

  return (
    <Link
      to={switchTo(target)}
      hrefLang={target}
      lang={target}
      aria-label={t('language.switchToLabel')}
      className={className}
    >
      {t('language.switchTo')}
    </Link>
  )
}
