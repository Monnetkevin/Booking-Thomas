import { useId, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, NavLink, useLocation } from 'react-router'
import { CloseIcon, MenuIcon } from '@/components/icons'
import { Logo } from '@/components/Logo'
import { Container } from '@/components/ui/Container'
import { LanguageSwitcher } from '@/i18n/LanguageSwitcher'
import { useLocale } from '@/i18n/useLocale'
import { cn } from '@/lib/cn'

export function SiteHeader() {
  const { t } = useTranslation()
  const { to } = useLocale()
  const { pathname } = useLocation()
  // Le menu est ouvert pour une page donnée : il se referme de lui-même en changeant de page.
  const [openOn, setOpenOn] = useState<string | null>(null)
  const open = openOn === pathname
  const menuId = useId()

  const links = [
    { to: to('/'), label: t('nav.home'), end: true },
    { to: to('/logements'), label: t('nav.apartments'), end: false },
    { to: to('/contact'), label: t('nav.contact'), end: false },
  ]

  const linkClass = ({ isActive }: { isActive: boolean }) =>
    cn(
      'flex min-h-11 items-center rounded-lg px-3 text-[15px] font-semibold no-underline',
      isActive ? 'text-accent' : 'text-ink hover:text-accent',
    )

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
      </Container>

      <nav id={menuId} aria-label={t('nav.main')} hidden={!open} className="border-t border-line md:hidden">
        <Container className="py-2">
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
        </Container>
      </nav>
    </header>
  )
}
