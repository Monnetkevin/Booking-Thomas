import { describe, expect, it } from 'vitest'
import { adminNavigation, canVisit, navigationFor, sidebarGroups, type AdminNavItem } from './navigation'

const items: AdminNavItem[] = [
  { to: '/admin', labelKey: 'admin.nav.dashboard', icon: null, roles: ['admin'], group: 'pilotage' },
  { to: '/admin/menage', labelKey: 'admin.nav.dashboard', icon: null, roles: ['admin', 'housekeeping'], group: 'suivi' },
]

describe('navigation du back-office', () => {
  it('montre au ménage le nombre de notifications non lues sur son entrée Notifications', () => {
    const entry = navigationFor('housekeeping', adminNavigation).find((item) => item.to === '/admin/notifications')
    expect(entry?.badge).toBe('unreadNotifications')
  })

  it('montre à chaque rôle ses seules entrées', () => {
    expect(navigationFor('admin', items).map((item) => item.to)).toEqual(['/admin', '/admin/menage'])
    expect(navigationFor('housekeeping', items).map((item) => item.to)).toEqual(['/admin/menage'])
  })

  it('ramène le ménage à ses seules pages, l’administrateur va partout', () => {
    expect(canVisit('/admin/menage', 'housekeeping', items)).toBe(true)
    expect(canVisit('/admin/menage/demain', 'housekeeping', items)).toBe(true)
    expect(canVisit('/admin', 'housekeeping', items)).toBe(false)
    expect(canVisit('/admin/menage-autre', 'housekeeping', items)).toBe(false)
    expect(canVisit('/admin/appartements', 'admin', items)).toBe(true)
  })

  it('donne au gestionnaire tout sauf tarifs, utilisateurs et réglages', () => {
    const pages = navigationFor('manager').map((item) => item.to)
    expect(pages).toContain('/admin/reservations')
    expect(pages).toContain('/admin/appartements')
    expect(pages).not.toContain('/admin/tarifs')
    expect(pages).not.toContain('/admin/utilisateurs')
    expect(pages).not.toContain('/admin/reglages')
    expect(pages).not.toContain('/admin/journal')
    expect(canVisit('/admin/reservations/BT-1', 'manager')).toBe(true)
    expect(canVisit('/admin/reglages', 'manager')).toBe(false)
    expect(canVisit('/admin/tarifs', 'manager')).toBe(false)
  })

  it('range les entrées par groupe du menu, sans le menu du compte', () => {
    const groups = sidebarGroups(navigationFor('manager'))
    expect(groups.map((entry) => entry.group)).toEqual(['pilotage', 'catalogue', 'suivi'])
    expect(groups.flatMap((entry) => entry.items).some((item) => item.group === 'account')).toBe(false)
    expect(sidebarGroups(navigationFor('housekeeping')).map((entry) => entry.group)).toEqual(['suivi'])
  })

  it('donne au ménage les notifications, et la gestion des comptes au seul administrateur', () => {
    expect(navigationFor('housekeeping').map((item) => item.to)).toEqual(['/admin/notifications', '/admin/notifications/preferences'])
    expect(navigationFor('admin').map((item) => item.to)).toContain('/admin/utilisateurs')
  })
})
