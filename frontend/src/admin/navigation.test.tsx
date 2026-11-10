import { describe, expect, it } from 'vitest'
import { navigationFor, type AdminNavItem } from './navigation'

const items: AdminNavItem[] = [
  { to: '/admin', labelKey: 'admin.nav.dashboard', icon: null, roles: ['admin'] },
  { to: '/admin/menage', labelKey: 'admin.nav.dashboard', icon: null, roles: ['admin', 'housekeeping'] },
]

describe('navigation du back-office', () => {
  it('montre à chaque rôle ses seules entrées', () => {
    expect(navigationFor('admin', items).map((item) => item.to)).toEqual(['/admin', '/admin/menage'])
    expect(navigationFor('housekeeping', items).map((item) => item.to)).toEqual(['/admin/menage'])
  })
})
