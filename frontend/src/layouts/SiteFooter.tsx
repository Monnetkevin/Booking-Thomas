import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { LockIcon } from '@/components/icons'
import { Container } from '@/components/ui/Container'
import { useLocale } from '@/i18n/useLocale'
import { ADMIN_PATH, legalNavigation } from '@/site/navigation'

// Année calculée au chargement de la page, pas à chaque rendu.
const YEAR = new Date().getFullYear()

/** Pied de page clair de la maquette v2 : mentions à gauche, pages légales à droite. */
export function SiteFooter() {
  const { t } = useTranslation()
  const { to } = useLocale()

  return (
    <footer className="mt-auto border-t border-line">
      <Container className="flex flex-col gap-4 py-7 text-sm text-muted sm:flex-row sm:items-center sm:justify-between">
        <p>
          © {YEAR} {t('site.name')}. {t('footer.rights')}
        </p>
        <ul className="flex flex-wrap items-center gap-x-5 gap-y-2">
          {legalNavigation.map((link) => (
            <li key={link.path}>
              <Link to={to(link.path)} className="text-muted underline hover:text-ink">
                {t(link.labelKey)}
              </Link>
            </li>
          ))}
          {/* Accès discret au back-office (non localisé, exclu des moteurs par robots.txt). */}
          <li>
            <Link to={ADMIN_PATH} className="inline-flex items-center gap-1.5 text-muted no-underline opacity-70 hover:text-ink hover:opacity-100">
              <LockIcon width={14} height={14} />
              {t('footer.admin')}
            </Link>
          </li>
        </ul>
      </Container>
    </footer>
  )
}
