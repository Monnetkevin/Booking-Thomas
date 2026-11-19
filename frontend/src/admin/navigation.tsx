import type { ReactNode } from 'react'
import type { Role } from '@/admin/auth/types'
import { BuildingIcon, HomeIcon, SyncIcon, TagIcon, UsersIcon } from '@/components/icons'

export interface AdminNavItem {
  to: string
  /** Clé de traduction du libellé. */
  labelKey:
    | 'admin.nav.dashboard'
    | 'admin.nav.apartments'
    | 'admin.nav.rates'
    | 'admin.nav.sync'
    | 'admin.nav.users'
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
  { to: '/admin/appartements', labelKey: 'admin.nav.apartments', icon: <BuildingIcon />, roles: ['admin'] },
  { to: '/admin/tarifs', labelKey: 'admin.nav.rates', icon: <TagIcon />, roles: ['admin'] },
  { to: '/admin/synchronisation', labelKey: 'admin.nav.sync', icon: <SyncIcon />, roles: ['admin'] },
  { to: '/admin/utilisateurs', labelKey: 'admin.nav.users', icon: <UsersIcon />, roles: ['admin'] },
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
