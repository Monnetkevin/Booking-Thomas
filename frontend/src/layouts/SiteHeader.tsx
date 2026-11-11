import { useId, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, NavLink, useLocation } from 'react-router'
import { CloseIcon, MenuIcon } from '@/components/icons'
import { Logo } from '@/components/Logo'
import { Container } from '@/components/ui/Container'
import { LanguageSwitcher } from '@/i18n/LanguageSwitcher'
import { useLocale } from '@/i18n/useLocale'
import { cn } from '@/lib/cn'

/** En-tête « pilule » de la maquette v2 : logements, quartier, contact, langue et bouton Réserver. */
export function SiteHeader() {
  const { t } = useTranslation()
  const { to } = useLocale()
  const { pathname } = useLocation()
  // Le menu est ouvert pour une page donnée : il se referme de lui-même en changeant de page.
  const [openOn, setOpenOn] = useState<string | null>(null)
  const open = openOn === pathname
  const menuId = useId()

  const linkClass = (active: boolean) =>
    cn(
      'flex min-h-11 items-center rounded-full px-3 text-[15px] font-semibold no-underline',
      active ? 'text-accent-text' : 'text-ink hover:text-accent-text',
    )

  const links = (
    <>
      <li>
        <NavLink to={to('/logements')} className={({ isActive }) => linkClass(isActive)}>
          {t('nav.apartments')}
        </NavLink>
      </li>
      <li>
        {/* Section « Bien placés » de l'accueil. */}
        <Link to={`${to('/')}#quartier`} className={linkClass(false)}>
          {t('nav.neighbourhood')}
        </Link>
      </li>
      <li>
        <NavLink to={to('/contact')} className={({ isActive }) => linkClass(isActive)}>
          {t('nav.contact')}
        </NavLink>
      </li>
      <li>
        <LanguageSwitcher className="flex min-h-11 items-center px-3 text-[15px] font-semibold" />
      </li>
    </>
  )

  return (
    <header className="sticky top-0 z-30 pt-3 sm:pt-5">
      <Container>
        <div className="flex min-h-[60px] items-center justify-between gap-4 rounded-full border border-line bg-white/95 py-2 pr-2 pl-5 shadow-[0_6px_20px_rgba(43,38,34,0.05)] backdrop-blur">
          <Link to={to('/')} className="text-ink no-underline hover:text-ink">
            <Logo />
          </Link>
          <nav aria-label={t('nav.main')} className="hidden md:block">
            <ul className="flex items-center gap-2 lg:gap-4">{links}</ul>
          </nav>
          <div className="flex items-center gap-1">
            <Link
              to={to('/logements')}
              className="hidden min-h-11 items-center rounded-full bg-ink px-5 text-sm font-bold text-white no-underline hover:bg-night-soft hover:text-white sm:inline-flex"
            >
              {t('nav.book')}
            </Link>
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
          </div>
        </div>
        <nav
          id={menuId}
          aria-label={t('nav.main')}
          hidden={!open}
          className="mt-2 rounded-[24px] border border-line bg-white p-2 shadow-[0_12px_32px_rgba(43,38,34,0.08)] md:hidden"
        >
          <ul className="flex flex-col">
            {links}
            <li className="sm:hidden">
              <Link to={to('/logements')} className={linkClass(false)}>
                {t('nav.book')}
              </Link>
            </li>
          </ul>
        </nav>
      </Container>
    </header>
  )
}
