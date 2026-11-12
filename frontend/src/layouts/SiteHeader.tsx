import { useId, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, NavLink, useLocation } from 'react-router'
import { CloseIcon, MenuIcon } from '@/components/icons'
import { Logo } from '@/components/Logo'
import { buttonClasses } from '@/components/ui/buttonClasses'
import { Container } from '@/components/ui/Container'
import { LanguageSwitcher } from '@/i18n/LanguageSwitcher'
import { useLocale } from '@/i18n/useLocale'
import { cn } from '@/lib/cn'
import { BOOKING_PATH, mainNavigation } from '@/site/navigation'
import { useScrolled } from './useScrolled'

/**
 * En-tête fixe en haut de l'écran, pleine largeur : logo, liens, langue et bouton Réserver.
 * Filet et ombre légère apparaissent dès que la page défile.
 */
export function SiteHeader() {
  const { t } = useTranslation()
  const { to } = useLocale()
  const { pathname } = useLocation()
  // Le menu est ouvert pour une page donnée : il se referme de lui-même en changeant de page.
  const [openOn, setOpenOn] = useState<string | null>(null)
  const open = openOn === pathname
  const menuId = useId()
  const scrolled = useScrolled()

  const linkClass = (active: boolean) =>
    cn(
      'flex min-h-11 items-center rounded-full px-3 text-[15px] font-semibold no-underline',
      active ? 'text-accent-text' : 'text-ink hover:text-accent-text',
    )

  const links = (
    <>
      {mainNavigation.map((link) => (
        <li key={link.path}>
          <NavLink to={to(link.path)} className={({ isActive }) => linkClass(isActive)}>
            {t(link.labelKey)}
          </NavLink>
        </li>
      ))}
      {/* Langue séparée des liens par un filet sur grand écran. */}
      <li className="md:ml-1 md:border-l md:border-line md:pl-2">
        <LanguageSwitcher className="flex min-h-11 items-center px-3 text-[15px] font-semibold" />
      </li>
    </>
  )

  return (
    <header
      className={cn(
        'sticky top-0 z-30 border-b bg-paper/95 backdrop-blur transition-shadow',
        scrolled ? 'border-line shadow-[0_6px_24px_rgba(43,38,34,0.08)]' : 'border-transparent',
      )}
    >
      <Container className="flex min-h-[72px] items-center justify-between gap-4">
        <Link to={to('/')} className="text-ink no-underline hover:text-ink">
          <Logo />
        </Link>
        <div className="flex items-center gap-2 lg:gap-6">
          <nav aria-label={t('nav.main')} className="hidden md:block">
            <ul className="flex items-center gap-1 lg:gap-3">{links}</ul>
          </nav>
          <Link to={to(BOOKING_PATH)} className={buttonClasses('dark', 'sm')}>
            {t('nav.book')}
          </Link>
          <button
            type="button"
            className="-mr-2 flex size-11 items-center justify-center rounded-full text-ink md:hidden"
            aria-expanded={open}
            aria-controls={menuId}
            aria-label={open ? t('nav.close') : t('nav.menu')}
            onClick={() => setOpenOn(open ? null : pathname)}
          >
            {open ? <CloseIcon /> : <MenuIcon />}
          </button>
        </div>
      </Container>
      <nav id={menuId} aria-label={t('nav.main')} hidden={!open} className="border-t border-line bg-paper md:hidden">
        <Container className="py-2">
          <ul className="flex flex-col">
            {links}
          </ul>
        </Container>
      </nav>
    </header>
  )
}
