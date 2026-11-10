import { useLayoutEffect, type ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { NavLink, Outlet, ScrollRestoration } from 'react-router'
import type { AdminNavItem } from '@/admin/navigation'
import { Logo } from '@/components/Logo'
import { applyLocale } from '@/i18n'
import { cn } from '@/lib/cn'

interface AdminLayoutProps {
  navigation: AdminNavItem[]
  footer?: ReactNode
}

/**
 * Mise en page du back-office : barre latérale sombre sur ordinateur, barre du haut
 * défilante sur téléphone. Ne connaît ni la session ni les droits : elle affiche ce qu'on lui donne.
 */
export function AdminLayout({ navigation, footer }: AdminLayoutProps) {
  const { t } = useTranslation()

  // Le back-office n'existe qu'en français.
  useLayoutEffect(() => applyLocale('fr'), [])

  const linkClass = ({ isActive }: { isActive: boolean }) =>
    cn(
      'flex min-h-11 shrink-0 items-center gap-3 rounded-[10px] px-3 text-[15px] whitespace-nowrap no-underline [&>svg]:shrink-0',
      isActive
        ? 'bg-night-soft font-bold text-white hover:text-white'
        : 'font-medium text-night-text hover:bg-night-soft hover:text-white',
    )

  return (
    <div className="flex min-h-dvh flex-col bg-sand lg:flex-row">
      <aside className="flex shrink-0 flex-col bg-night text-night-text lg:sticky lg:top-0 lg:h-dvh lg:w-60">
        <div className="flex items-center px-4 pt-4 pb-2 text-white lg:px-5 lg:pt-5 lg:pb-5">
          <Logo />
        </div>
        <nav aria-label={t('admin.nav.label')} className="px-3 pb-3 lg:flex-1">
          <ul className="flex gap-1 overflow-x-auto lg:flex-col">
            {navigation.map((item) => (
              <li key={item.to}>
                <NavLink to={item.to} end={item.end} className={linkClass}>
                  {item.icon}
                  {t(item.labelKey)}
                </NavLink>
              </li>
            ))}
          </ul>
        </nav>
        {footer && <div className="border-t border-night-soft px-3 py-3">{footer}</div>}
      </aside>

      <main className="min-w-0 flex-1 px-4 py-6 sm:px-8 sm:py-8">
        <Outlet />
      </main>
      <ScrollRestoration />
    </div>
  )
}
