import type { ReactNode } from 'react'
import type { Role } from '@/admin/auth/types'
import { BarChartIcon, BellIcon, BuildingIcon, CalendarIcon, GridIcon, HomeIcon, ListIcon, MailIcon, SettingsIcon, SyncIcon, TagIcon, UploadIcon, UsersIcon } from '@/components/icons'

/** Compteur affiché à côté d'une entrée, lu dans les compteurs de la barre. */
export type NavBadge = 'conflicts' | 'failingFeeds' | 'unreadMessages'

/** Groupe du menu latéral ; « account » : menu du compte, en haut à droite. */
export type NavGroup = 'pilotage' | 'catalogue' | 'suivi' | 'account'

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
    | 'admin.nav.platformImport'
    | 'admin.nav.notifications'
    | 'admin.nav.notificationPreferences'
    | 'admin.nav.users'
    | 'admin.nav.audit'
    | 'admin.nav.messages'
    | 'admin.nav.settings'
  icon: ReactNode
  roles: Role[]
  group: NavGroup
  badge?: NavBadge
  end?: boolean
}

/** Administrateur et gestionnaire : tout le back-office sauf tarifs, utilisateurs et réglages. */
const STAFF: Role[] = ['admin', 'manager']
const EVERYONE: Role[] = ['admin', 'manager', 'housekeeping']

/** Groupes du menu latéral, dans l'ordre d'affichage. */
const NAV_GROUPS = ['pilotage', 'catalogue', 'suivi'] as const satisfies readonly NavGroup[]

/**
 * Entrées du back-office. Chaque module s'ajoute ici, sans toucher à la mise en page
 * (ouvert à l'extension, fermé à la modification).
 */
export const adminNavigation: AdminNavItem[] = [
  { to: '/admin', labelKey: 'admin.nav.dashboard', icon: <HomeIcon />, roles: STAFF, group: 'pilotage', end: true },
  { to: '/admin/planning', labelKey: 'admin.nav.planning', icon: <GridIcon />, roles: STAFF, group: 'pilotage' },
  { to: '/admin/reservations', labelKey: 'admin.nav.bookings', icon: <CalendarIcon />, roles: STAFF, group: 'pilotage', badge: 'conflicts' },
  { to: '/admin/appartements', labelKey: 'admin.nav.apartments', icon: <BuildingIcon />, roles: STAFF, group: 'catalogue' },
  { to: '/admin/tarifs', labelKey: 'admin.nav.rates', icon: <TagIcon />, roles: ['admin'], group: 'catalogue' },
  { to: '/admin/messages', labelKey: 'admin.nav.messages', icon: <MailIcon />, roles: STAFF, group: 'suivi', badge: 'unreadMessages' },
  { to: '/admin/statistiques', labelKey: 'admin.nav.statistics', icon: <BarChartIcon />, roles: STAFF, group: 'suivi' },
  { to: '/admin/revenus-plateformes', labelKey: 'admin.nav.platformImport', icon: <UploadIcon />, roles: ['admin'], group: 'suivi' },
  { to: '/admin/synchronisation', labelKey: 'admin.nav.sync', icon: <SyncIcon />, roles: STAFF, group: 'suivi', badge: 'failingFeeds' },
  { to: '/admin/journal', labelKey: 'admin.nav.audit', icon: <ListIcon />, roles: STAFF, group: 'suivi' },
  // Le ménage n'a pas le bouton « Alertes » : ses notifications restent une entrée du menu.
  { to: '/admin/notifications', labelKey: 'admin.nav.notifications', icon: <BellIcon />, roles: ['housekeeping'], group: 'suivi' },
  { to: '/admin/reglages', labelKey: 'admin.nav.settings', icon: <SettingsIcon />, roles: ['admin'], group: 'account' },
  { to: '/admin/utilisateurs', labelKey: 'admin.nav.users', icon: <UsersIcon />, roles: ['admin'], group: 'account' },
  { to: '/admin/notifications/preferences', labelKey: 'admin.nav.notificationPreferences', icon: <MailIcon />, roles: EVERYONE, group: 'account' },
]

/** Pages hors menu ouvertes aux rôles qui reçoivent des notifications (liste complète). */
const NOTIFICATIONS_PAGE = '/admin/notifications'

export function navigationFor(role: Role, items: AdminNavItem[] = adminNavigation): AdminNavItem[] {
  return items.filter((item) => item.roles.includes(role))
}

/**
 * Page accessible au rôle : l'administrateur va partout ; les autres (gestionnaire, ménage) seulement sous leurs
 * entrées de menu. L'API reste seule juge (403) ; ceci évite d'afficher une page vide.
 */
export function canVisit(pathname: string, role: Role, items: AdminNavItem[] = adminNavigation): boolean {
  if (role === 'admin') return true
  if (pathname === NOTIFICATIONS_PAGE) return true
  // Une entrée « end » (le tableau de bord, /admin) ne couvre que sa propre adresse.
  return navigationFor(role, items).some((item) => pathname === item.to || (!item.end && pathname.startsWith(`${item.to}/`)))
}

/** Entrées du menu latéral, par groupe (groupes vides omis). */
export function sidebarGroups(items: AdminNavItem[]): { group: (typeof NAV_GROUPS)[number]; items: AdminNavItem[] }[] {
  return NAV_GROUPS.map((group) => ({ group, items: items.filter((item) => item.group === group) })).filter((entry) => entry.items.length > 0)
}
