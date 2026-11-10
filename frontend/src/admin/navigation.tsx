import type { ReactNode } from 'react'
import type { Role } from '@/admin/auth/types'
import { HomeIcon } from '@/components/icons'

export interface AdminNavItem {
  to: string
  /** Clé de traduction du libellé. */
  labelKey: 'admin.nav.dashboard'
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
]

export function navigationFor(role: Role, items: AdminNavItem[] = adminNavigation): AdminNavItem[] {
  return items.filter((item) => item.roles.includes(role))
}
