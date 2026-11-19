import { describe, expect, it } from 'vitest'
import { badgeLabel, notificationLink, relativeTime } from './notificationFormat'

const now = new Date('2026-11-19T15:00:00')

describe('mise en forme des notifications', () => {
  it('donne une date relative courte, puis la date', () => {
    expect(relativeTime('2026-11-19T14:59:40', now)).toEqual({ key: 'now' })
    expect(relativeTime('2026-11-19T14:35:00', now)).toEqual({ key: 'minutes', count: 25 })
    expect(relativeTime('2026-11-19T09:00:00', now)).toEqual({ key: 'hours', count: 6 })
    expect(relativeTime('2026-11-18T23:00:00', now)).toEqual({ key: 'yesterday' })
    expect(relativeTime('2026-11-10T10:00:00', now)).toEqual({ key: 'date' })
  })

  it('ne suit que les liens du back-office', () => {
    expect(notificationLink('/admin/synchronisation')).toBe('/admin/synchronisation')
    expect(notificationLink('https://exemple.fr/admin')).toBeNull()
    expect(notificationLink('//exemple.fr/admin')).toBeNull()
    expect(notificationLink('/logements')).toBeNull()
    expect(notificationLink(null)).toBeNull()
  })

  it('affiche 9+ au-delà de neuf non lues, rien à zéro', () => {
    expect(badgeLabel(0)).toBeNull()
    expect(badgeLabel(4)).toBe('4')
    expect(badgeLabel(12)).toBe('9+')
  })
})
