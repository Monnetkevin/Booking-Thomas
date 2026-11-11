import { useId, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, NavLink, useLocation } from 'react-router'
import { CloseIcon, MenuIcon } from '@/components/icons'
import { Logo } from '@/components/Logo'
import { Container } from '@/components/ui/Container'
import { LanguageSwitcher } from '@/i18n/LanguageSwitcher'
import { useLocale } from '@/i18n/useLocale'
import { cn } from '@/lib/cn'

/**
 * En-tête du site public. Sur l'accueil, version « pilule » de la maquette v2 (liens vers
 * les logements, le quartier et le contact, bouton Réserver) ; ailleurs, barre classique.
 */
export function SiteHeader() {
  const { t } = useTranslation()
  const { to } = useLocale()
  const { pathname } = useLocation()
  // Le menu est ouvert pour une page donnée : il se referme de lui-même en changeant de page.
  const [openOn, setOpenOn] = useState<string | null>(null)
  const open = openOn === pathname
  const menuId = useId()
  const isHome = (pathname.replace(/\/$/, '') || '/') === to('/')

  const links = isHome
    ? [
        { to: to('/logements'), label: t('nav.apartments'), end: false },
        { to: `${to('/')}#quartier`, label: t('nav.neighbourhood'), end: true },
        { to: to('/contact'), label: t('nav.contact'), end: false },
      ]
    : [
        { to: to('/'), label: t('nav.home'), end: true },
        { to: to('/logements'), label: t('nav.apartments'), end: false },
        { to: to('/contact'), label: t('nav.contact'), end: false },
      ]

  const linkClass = ({ isActive }: { isActive: boolean }) =>
    cn(
      'flex min-h-11 items-center rounded-lg px-3 text-[15px] font-semibold no-underline',
      isActive && !isHome ? 'text-accent-text' : 'text-ink hover:text-accent-text',
    )

  const menuButton = (
    <button
      type="button"
      className="flex size-11 items-center justify-center rounded-full text-ink md:hidden"
      aria-expanded={open}
      aria-controls={menuId}
      aria-label={open ? t('nav.close') : t('nav.menu')}
      onClick={() => setOpenOn(open ? null : pathname)}
    >
      {open ? <CloseIcon /> : <MenuIcon />}
    </button>
  )

  const mobileLinks = (
    <ul className="flex flex-col">
      {links.map((link) => (
        <li key={link.to}>
          <NavLink to={link.to} end={link.end} className={linkClass}>
            {link.label}
          </NavLink>
        </li>
      ))}
      <li>
        <LanguageSwitcher className="flex min-h-11 items-center px-3 text-[15px] font-semibold" />
      </li>
    </ul>
  )

  if (isHome) {
    return (
      <header className="sticky top-0 z-30 pt-3 sm:pt-5">
        <Container>
          <div className="flex min-h-[60px] items-center justify-between gap-4 rounded-full border border-line bg-white/85 py-2 pr-2 pl-5 backdrop-blur">
            <Link to={to('/')} className="text-ink no-underline hover:text-ink">
              <Logo />
            </Link>
            <nav aria-label={t('nav.main')} className="hidden md:block">
              <ul className="flex items-center gap-2 lg:gap-4">
                {links.map((link) => (
                  <li key={link.to}>
                    <NavLink to={link.to} end={link.end} className={linkClass}>
                      {link.label}
                    </NavLink>
                  </li>
                ))}
                <li>
                  <LanguageSwitcher className="flex min-h-11 items-center px-3 text-[15px] font-semibold" />
                </li>
              </ul>
            </nav>
            <div className="flex items-center gap-1">
              <Link
                to={to('/logements')}
                className="hidden min-h-11 items-center rounded-full bg-ink px-5 text-sm font-bold text-white no-underline hover:bg-night-soft hover:text-white sm:inline-flex"
              >
                {t('nav.book')}
              </Link>
              {menuButton}
            </div>
          </div>
          <nav
            id={menuId}
            aria-label={t('nav.main')}
            hidden={!open}
            className="mt-2 rounded-[24px] border border-line bg-white p-2 shadow-[0_12px_32px_rgba(43,38,34,0.08)] md:hidden"
          >
            {mobileLinks}
          </nav>
        </Container>
      </header>
    )
  }

  return (
    <header className="sticky top-0 z-30 border-b border-line bg-paper/95 backdrop-blur">
      <Container className="flex min-h-16 items-center justify-between gap-4">
        <Link to={to('/')} className="text-ink no-underline hover:text-ink">
          <Logo />
        </Link>

        <nav aria-label={t('nav.main')} className="hidden md:block">
          <ul className="flex items-center gap-1">
            {links.map((link) => (
              <li key={link.to}>
                <NavLink to={link.to} end={link.end} className={linkClass}>
                  {link.label}
                </NavLink>
              </li>
            ))}
            <li className="ml-2 border-l border-line pl-3">
              <LanguageSwitcher className="flex min-h-11 items-center text-[15px] font-semibold" />
            </li>
          </ul>
        </nav>

        <div className="-mr-2">{menuButton}</div>
      </Container>

      <nav id={menuId} aria-label={t('nav.main')} hidden={!open} className="border-t border-line md:hidden">
        <Container className="py-2">{mobileLinks}</Container>
      </nav>
    </header>
  )
}
