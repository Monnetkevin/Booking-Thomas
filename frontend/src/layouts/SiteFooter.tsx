import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { Container } from '@/components/ui/Container'
import { useLocale } from '@/i18n/useLocale'

// Année calculée au chargement de la page, pas à chaque rendu.
const YEAR = new Date().getFullYear()

export function SiteFooter() {
  const { t } = useTranslation()
  const { to } = useLocale()

  const links = [
    { to: to('/mentions-legales'), label: t('footer.legal') },
    { to: to('/cgv'), label: t('footer.terms') },
    { to: to('/confidentialite'), label: t('footer.privacy') },
  ]

  return (
    <footer className="mt-auto bg-night text-night-text">
      <Container className="flex flex-col gap-4 py-8 text-sm sm:flex-row sm:items-center sm:justify-between">
        <ul className="flex flex-wrap gap-x-5 gap-y-2">
          {links.map((link) => (
            <li key={link.to}>
              <Link to={link.to} className="text-night-text underline hover:text-white">
                {link.label}
              </Link>
            </li>
          ))}
        </ul>
        <p>
          © {YEAR} {t('site.name')}. {t('footer.rights')}
        </p>
      </Container>
    </footer>
  )
}
