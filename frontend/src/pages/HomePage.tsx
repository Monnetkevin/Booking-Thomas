import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { buttonClasses } from '@/components/ui/buttonClasses'
import { Container } from '@/components/ui/Container'
import { useLocale } from '@/i18n/useLocale'

/** Accueil provisoire : le contenu complet arrive avec le jalon « Site vitrine » (#17). */
export function HomePage() {
  const { t } = useTranslation()
  const { to } = useLocale()

  return (
    <Container className="flex flex-col gap-5 py-12 sm:py-20">
      <p className="text-xs font-semibold tracking-[0.08em] text-accent uppercase">{t('site.tagline')}</p>
      <h1 className="max-w-2xl text-4xl leading-[1.1] font-semibold sm:text-5xl">{t('home.title')}</h1>
      <p className="max-w-xl text-base leading-relaxed text-muted sm:text-lg">{t('home.intro')}</p>
      <div>
        <Link to={to('/logements')} className={buttonClasses('primary', 'lg')}>
          {t('home.cta')}
        </Link>
      </div>
    </Container>
  )
}
