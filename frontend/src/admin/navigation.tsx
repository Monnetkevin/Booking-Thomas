import type { ReactNode } from 'react'
import type { Role } from '@/admin/auth/types'
import { BarChartIcon, BellIcon, BuildingIcon, CalendarIcon, GridIcon, HomeIcon, ListIcon, MailIcon, SettingsIcon, SyncIcon, TagIcon, UsersIcon } from '@/components/icons'

export interface AdminNavItem {
  to: string
  /** Clé de traduction du libellé. */
  labelKey:
    | 'admin.nav.dashboard'
    | 'admin.nav.apartments'
    | 'admin.nav.rates'
    | 'admin.nav.sync'
    | 'admin.nav.bookings'
    | 'admin.nav.planning'
    | 'admin.nav.statistics'
    | 'admin.nav.notifications'
    | 'admin.nav.users'
    | 'admin.nav.audit'
    | 'admin.nav.messages'
    | 'admin.nav.settings'
  icon: ReactNode
  roles: Role[]
  end?: boolean
}

/**
 * Entrées du menu du back-office. Chaque module s'ajoute ici, sans toucher à la mise
 * en page (ouvert à l'extension, fermé à la modification).
 */
export const adminNavigation: AdminNavItem[] = [
  { to: '/admin', labelKey: 'admin.nav.dashboard', icon: <HomeIcon />, roles: ['admin'], end: true },
  { to: '/admin/planning', labelKey: 'admin.nav.planning', icon: <GridIcon />, roles: ['admin'] },
  { to: '/admin/reservations', labelKey: 'admin.nav.bookings', icon: <CalendarIcon />, roles: ['admin'] },
  { to: '/admin/statistiques', labelKey: 'admin.nav.statistics', icon: <BarChartIcon />, roles: ['admin'] },
  { to: '/admin/appartements', labelKey: 'admin.nav.apartments', icon: <BuildingIcon />, roles: ['admin'] },
  { to: '/admin/tarifs', labelKey: 'admin.nav.rates', icon: <TagIcon />, roles: ['admin'] },
  { to: '/admin/synchronisation', labelKey: 'admin.nav.sync', icon: <SyncIcon />, roles: ['admin'] },
  { to: '/admin/messages', labelKey: 'admin.nav.messages', icon: <MailIcon />, roles: ['admin'] },
  { to: '/admin/notifications', labelKey: 'admin.nav.notifications', icon: <BellIcon />, roles: ['admin', 'housekeeping'] },
  { to: '/admin/utilisateurs', labelKey: 'admin.nav.users', icon: <UsersIcon />, roles: ['admin'] },
  { to: '/admin/reglages', labelKey: 'admin.nav.settings', icon: <SettingsIcon />, roles: ['admin'] },
  { to: '/admin/journal', labelKey: 'admin.nav.audit', icon: <ListIcon />, roles: ['admin'] },
]

export function navigationFor(role: Role, items: AdminNavItem[] = adminNavigation): AdminNavItem[] {
  return items.filter((item) => item.roles.includes(role))
}

/**
 * Page accessible au rôle : l'administrateur va partout ; les autres seulement sous leurs
 * entrées de menu. L'API reste seule juge (403) ; ceci évite d'afficher une page vide.
 */
export function canVisit(pathname: string, role: Role, items: AdminNavItem[] = adminNavigation): boolean {
  if (role === 'admin') return true
  return navigationFor(role, items).some((item) => pathname === item.to || pathname.startsWith(`${item.to}/`))
}
