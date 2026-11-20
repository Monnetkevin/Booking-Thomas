import { useEffect, useLayoutEffect, useState, type ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { NavLink, Outlet, ScrollRestoration, useLocation } from 'react-router'
import { sidebarGroups, type AdminNavItem, type NavBadge } from '@/admin/navigation'
import { badgeLabel } from '@/admin/notifications/notificationFormat'
import { ArrowUpRightIcon, MenuIcon } from '@/components/icons'
import { LogoMark } from '@/components/Logo'
import { applyLocale } from '@/i18n'
import { cn } from '@/lib/cn'
import { useMediaQuery } from '@/lib/useMediaQuery'
import './typographie.css'

const COLLAPSED_KEY = 'admin.nav.collapsed'
const DESKTOP = '(min-width: 1024px)'

function readCollapsed(): boolean {
  try {
    return localStorage.getItem(COLLAPSED_KEY) === '1'
  } catch {
    return false
  }
}

function saveCollapsed(collapsed: boolean) {
  try {
    localStorage.setItem(COLLAPSED_KEY, collapsed ? '1' : '0')
  } catch {
    // stockage indisponible (navigation privée) : le choix vaut pour la session
  }
}

interface AdminLayoutProps {
  /** Entrées autorisées au rôle ; seules celles des groupes du menu latéral y sont montrées. */
  navigation: AdminNavItem[]
  /** Compteurs affichés à côté des entrées (conflits, calendriers en échec, messages). */
  badges?: Partial<Record<NavBadge, number>>
  /** Recherche rapide, au centre de la barre du haut. */
  search?: ReactNode
  /** Menu du compte, à droite de la barre du haut. */
  account: ReactNode
  /** Bouton « Alertes » en tête du menu ; reçoit l'état réduit du menu. */
  alerts?: (collapsed: boolean) => ReactNode
  /** Pied du menu (état de la synchronisation). */
  footer?: ReactNode
}

/**
 * Mise en page du back-office : barre du haut, menu latéral groupé, réductible sur ordinateur
 * (choix mémorisé) et en tiroir sur tablette et téléphone. Ne connaît ni la session ni les
 * droits : elle affiche ce qu'on lui donne.
 */
export function AdminLayout({ navigation, badges = {}, search, account, alerts, footer }: AdminLayoutProps) {
  const { t } = useTranslation()
  const { pathname } = useLocation()
  const desktop = useMediaQuery(DESKTOP)
  const [collapsed, setCollapsed] = useState(readCollapsed)
  // Tiroir ouvert pour une page donnée : il se referme de lui-même quand on change de page
  // ou qu'on passe sur grand écran.
  const [drawerOpenAt, setDrawerOpenAt] = useState<string | null>(null)
  const drawerOpen = !desktop && drawerOpenAt === pathname
  const closeDrawer = () => setDrawerOpenAt(null)
  const reduced = desktop && collapsed

  // Le back-office n'existe qu'en français.
  useLayoutEffect(() => applyLocale('fr'), [])

  useEffect(() => {
    if (!drawerOpen) return
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') setDrawerOpenAt(null)
    }
    document.addEventListener('keydown', onKeyDown)
    return () => document.removeEventListener('keydown', onKeyDown)
  }, [drawerOpen])

  const toggle = () => {
    if (!desktop) {
      setDrawerOpenAt(drawerOpen ? null : pathname)
      return
    }
    saveCollapsed(!collapsed)
    setCollapsed(!collapsed)
  }

  const linkClass = ({ isActive }: { isActive: boolean }) =>
    cn(
      'flex min-h-11 items-center gap-3 rounded-xl text-[15px] whitespace-nowrap no-underline [&>svg]:size-5 [&>svg]:shrink-0',
      reduced ? 'justify-center px-0' : 'px-3',
      isActive
        ? 'bg-accent-soft font-bold text-accent-strong hover:text-accent-strong [&>svg]:text-accent'
        : 'font-medium text-ink hover:bg-sand hover:text-ink [&>svg]:text-muted',
    )

  return (
    <div className="admin-typo flex min-h-dvh flex-col bg-sand">
      {/* z-30 : la barre et ses menus passent au-dessus des éléments positionnés des pages
          (colonne figée du planning, barres d'enregistrement). */}
      <header className="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-3 border-b border-line bg-white px-3 sm:gap-6 sm:px-6">
        <button
          type="button"
          onClick={toggle}
          aria-label={t('admin.nav.toggle')}
          aria-expanded={desktop ? !collapsed : drawerOpen}
          aria-controls="admin-nav"
          className="flex size-11 shrink-0 items-center justify-center rounded-xl border border-line hover:bg-sand"
        >
          <MenuIcon width={20} height={20} />
        </button>
        <NavLink to="/admin" end className="flex shrink-0 items-center gap-2.5 text-[17px] font-bold text-ink no-underline hover:text-ink">
          <LogoMark />
          <span className="hidden sm:inline">{t('site.name')}</span>
          <span className="hidden rounded-full bg-accent-soft px-2 py-0.5 text-[11px] font-bold tracking-wide text-accent-strong md:inline">
            {t('admin.badge')}
          </span>
        </NavLink>
        <div className="flex min-w-0 flex-1 justify-end md:justify-start">{search}</div>
        <a
          href="/"
          target="_blank"
          rel="noopener"
          className="hidden items-center gap-1.5 px-2 text-sm font-semibold text-ink no-underline hover:text-accent-strong xl:flex"
        >
          {t('admin.nav.viewSite')}
          <ArrowUpRightIcon width={15} height={15} />
        </a>
        <span aria-hidden="true" className="hidden h-7 w-px bg-line xl:block" />
        {account}
      </header>

      <div className="relative flex min-h-0 flex-1">
        <nav
          id="admin-nav"
          aria-label={t('admin.nav.label')}
          className={cn(
            'flex shrink-0 flex-col gap-5 border-r border-line bg-white px-3.5 py-5',
            // Ordinateur : colonne collée sous la barre, défilante si l'écran est peu haut.
            'lg:sticky lg:top-16 lg:h-[calc(100dvh-4rem)] lg:overflow-y-auto',
            reduced ? 'lg:w-[76px]' : 'lg:w-64',
            // Tablette et téléphone : tiroir par-dessus le contenu.
            'max-lg:fixed max-lg:top-16 max-lg:bottom-0 max-lg:left-0 max-lg:z-30 max-lg:w-72 max-lg:overflow-y-auto max-lg:shadow-2xl max-lg:transition-transform',
            !drawerOpen && 'max-lg:invisible max-lg:-translate-x-full',
          )}
        >
          {alerts?.(reduced)}
          {sidebarGroups(navigation).map(({ group, items }) => (
            <div key={group} className="flex flex-col gap-0.5">
              {!reduced && (
                <span className="px-3 pb-1.5 text-[11px] font-bold tracking-[0.08em] text-muted uppercase">{t(`admin.nav.groups.${group}`)}</span>
              )}
              <ul className="flex flex-col gap-0.5">
                {items.map((item) => {
                  const count = item.badge ? (badges[item.badge] ?? 0) : 0
                  const label = t(item.labelKey)
                  return (
                    <li key={item.to}>
                      <NavLink
                        to={item.to}
                        end={item.end}
                        className={linkClass}
                        aria-label={reduced ? (count > 0 ? t('admin.nav.withBadge', { label, count }) : label) : undefined}
                        title={reduced ? label : undefined}
                      >
                        {item.icon}
                        {!reduced && <span className="flex-1">{label}</span>}
                        {!reduced && count > 0 && (
                          <span className="min-w-6 rounded-full bg-accent px-1.5 text-center text-xs leading-5 font-bold text-white">
                            <span aria-hidden="true">{badgeLabel(count)}</span>
                            <span className="sr-only">{t('admin.nav.badge', { count })}</span>
                          </span>
                        )}
                      </NavLink>
                    </li>
                  )
                })}
              </ul>
            </div>
          ))}
          {footer && !reduced && <div className="mt-auto">{footer}</div>}
        </nav>

        {drawerOpen && (
          <button
            type="button"
            aria-label={t('admin.nav.close')}
            onClick={closeDrawer}
            className="fixed inset-0 top-16 z-20 bg-ink/40"
          />
        )}

        <main className="min-w-0 flex-1 px-4 py-6 sm:px-8 sm:py-7">
          <Outlet />
        </main>
      </div>
      <ScrollRestoration />
    </div>
  )
}
