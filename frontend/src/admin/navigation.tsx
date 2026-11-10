import type { ReactNode } from 'react'
import type { Role } from '@/admin/auth/types'
import { BuildingIcon, HomeIcon, TagIcon } from '@/components/icons'

export interface AdminNavItem {
  to: string
  /** Clé de traduction du libellé. */
  labelKey: 'admin.nav.dashboard' | 'admin.nav.apartments' | 'admin.nav.rates'
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
]

export function navigationFor(role: Role, items: AdminNavItem[] = adminNavigation): AdminNavItem[] {
  return items.filter((item) => item.roles.includes(role))
}
