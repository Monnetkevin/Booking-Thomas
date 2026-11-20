import { describe, expect, it } from 'vitest'
import { canVisit, navigationFor, type AdminNavItem } from './navigation'

const items: AdminNavItem[] = [
  { to: '/admin', labelKey: 'admin.nav.dashboard', icon: null, roles: ['admin'] },
  { to: '/admin/menage', labelKey: 'admin.nav.dashboard', icon: null, roles: ['admin', 'housekeeping'] },
]

describe('navigation du back-office', () => {
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
    expect(canVisit('/admin/reservations/BT-1', 'manager')).toBe(true)
    expect(canVisit('/admin/reglages', 'manager')).toBe(false)
    expect(canVisit('/admin/tarifs', 'manager')).toBe(false)
  })

  it('donne au ménage les notifications, et la gestion des comptes au seul administrateur', () => {
    expect(navigationFor('housekeeping').map((item) => item.to)).toEqual(['/admin/notifications'])
    expect(navigationFor('admin').map((item) => item.to)).toContain('/admin/utilisateurs')
  })
})
