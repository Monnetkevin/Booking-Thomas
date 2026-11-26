import type { ReactNode } from 'react'
import type { Role } from '@/admin/auth/types'
import { BarChartIcon, BellIcon, BroomIcon, BuildingIcon, GiftIcon, CalendarIcon, GridIcon, HomeIcon, ListIcon, MailIcon, PercentIcon, SettingsIcon, StarIcon, SyncIcon, TagIcon, UsersIcon, WalletIcon } from '@/components/icons'

/** Compteur affiché à côté d'une entrée, lu dans les compteurs de la barre. */
export type NavBadge = 'conflicts' | 'failingFeeds' | 'unreadMessages' | 'pendingReviews' | 'unreadNotifications'

/** Groupe du menu latéral ; « account » : menu du compte, en haut à droite. */
export type NavGroup = 'pilotage' | 'catalogue' | 'suivi' | 'account'

export interface AdminNavItem {
  to: string
  /** Clé de traduction du libellé. */
  labelKey:
    | 'admin.nav.dashboard'
    | 'admin.nav.apartments'
    | 'admin.nav.rates'
    | 'admin.nav.extras'
    | 'admin.nav.coupons'
    | 'admin.nav.sync'
    | 'admin.nav.bookings'
    | 'admin.nav.planning'
    | 'admin.nav.cleaning'
    | 'admin.nav.statistics'
    | 'admin.nav.platformRevenue'
    | 'admin.nav.notifications'
    | 'admin.nav.notificationPreferences'
    | 'admin.nav.users'
    | 'admin.nav.audit'
    | 'admin.nav.messages'
    | 'admin.nav.reviews'
    | 'admin.nav.settings'
  icon: ReactNode
  roles: Role[]
  group: NavGroup
  badge?: NavBadge
  end?: boolean
}

/** Le propriétaire (administrateur) gère tout le back-office. */
const STAFF: Role[] = ['admin']
const EVERYONE: Role[] = ['admin', 'housekeeping']

/** Groupes du menu latéral, dans l'ordre d'affichage. */
const NAV_GROUPS = ['pilotage', 'catalogue', 'suivi'] as const satisfies readonly NavGroup[]

/**
 * Entrées du back-office. Chaque module s'ajoute ici, sans toucher à la mise en page
 * (ouvert à l'extension, fermé à la modification).
 */
export const adminNavigation: AdminNavItem[] = [
  { to: '/admin', labelKey: 'admin.nav.dashboard', icon: <HomeIcon />, roles: STAFF, group: 'pilotage', end: true },
  { to: '/admin/planning', labelKey: 'admin.nav.planning', icon: <GridIcon />, roles: STAFF, group: 'pilotage' },
  // Première entrée du ménage : c'est là qu'il arrive après connexion.
  { to: '/admin/menage', labelKey: 'admin.nav.cleaning', icon: <BroomIcon />, roles: EVERYONE, group: 'pilotage' },
  { to: '/admin/reservations', labelKey: 'admin.nav.bookings', icon: <CalendarIcon />, roles: STAFF, group: 'pilotage', badge: 'conflicts' },
  { to: '/admin/appartements', labelKey: 'admin.nav.apartments', icon: <BuildingIcon />, roles: STAFF, group: 'catalogue' },
  { to: '/admin/tarifs', labelKey: 'admin.nav.rates', icon: <TagIcon />, roles: ['admin'], group: 'catalogue' },
  { to: '/admin/options', labelKey: 'admin.nav.extras', icon: <GiftIcon />, roles: ['admin'], group: 'catalogue' },
  { to: '/admin/coupons', labelKey: 'admin.nav.coupons', icon: <PercentIcon />, roles: ['admin'], group: 'catalogue' },
  { to: '/admin/messages', labelKey: 'admin.nav.messages', icon: <MailIcon />, roles: STAFF, group: 'suivi', badge: 'unreadMessages' },
  { to: '/admin/avis', labelKey: 'admin.nav.reviews', icon: <StarIcon />, roles: STAFF, group: 'suivi', badge: 'pendingReviews' },
  { to: '/admin/statistiques', labelKey: 'admin.nav.statistics', icon: <BarChartIcon />, roles: STAFF, group: 'suivi' },
  { to: '/admin/revenus-plateformes', labelKey: 'admin.nav.platformRevenue', icon: <WalletIcon />, roles: ['admin'], group: 'suivi' },
  { to: '/admin/synchronisation', labelKey: 'admin.nav.sync', icon: <SyncIcon />, roles: STAFF, group: 'suivi', badge: 'failingFeeds' },
  // Le journal contient réglages, tarifs et comptes : administrateur seulement.
  { to: '/admin/journal', labelKey: 'admin.nav.audit', icon: <ListIcon />, roles: ['admin'], group: 'suivi' },
  // Le ménage n'a pas le bouton « Alertes » : ses notifications restent une entrée du menu, avec le nombre de non lues.
  { to: '/admin/notifications', labelKey: 'admin.nav.notifications', icon: <BellIcon />, roles: ['housekeeping'], group: 'suivi', badge: 'unreadNotifications' },
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
 * Page accessible au rôle : l'administrateur va partout ; les autres (ménage) seulement sous leurs
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
