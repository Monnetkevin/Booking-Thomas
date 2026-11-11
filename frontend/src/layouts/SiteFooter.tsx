import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { Container } from '@/components/ui/Container'
import { useLocale } from '@/i18n/useLocale'

// Année calculée au chargement de la page, pas à chaque rendu.
const YEAR = new Date().getFullYear()

/** Pied de page clair de la maquette v2 : mentions à gauche, pages légales à droite. */
export function SiteFooter() {
  const { t } = useTranslation()
  const { to } = useLocale()

  const links = [
    { to: to('/mentions-legales'), label: t('footer.legal') },
    { to: to('/cgv'), label: t('footer.terms') },
    { to: to('/confidentialite'), label: t('footer.privacy') },
  ]

  return (
    <footer className="mt-auto border-t border-line">
      <Container className="flex flex-col gap-4 py-7 text-sm text-muted sm:flex-row sm:items-center sm:justify-between">
        <p>
          © {YEAR} {t('site.name')}. {t('footer.rights')}
        </p>
        <ul className="flex flex-wrap gap-x-5 gap-y-2">
          {links.map((link) => (
            <li key={link.to}>
              <Link to={link.to} className="text-muted underline hover:text-ink">
                {link.label}
              </Link>
            </li>
          ))}
        </ul>
      </Container>
    </footer>
  )
}
