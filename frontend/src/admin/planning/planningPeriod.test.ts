import { describe, expect, it } from 'vitest'
import { planningFigures, shortGuestName } from './planningFigures'
import { periodContaining, periodIncludes, shiftPeriod } from './planningPeriod'
import type { Planning } from './types'

describe('périodes du planning', () => {
  it('cale le mois civil et la semaine du lundi', () => {
    expect(periodContaining('month', '2026-11-20')).toEqual({ view: 'month', from: '2026-11-01', days: 30 })
    expect(periodContaining('month', '2028-02-10')).toEqual({ view: 'month', from: '2028-02-01', days: 29 })
    expect(periodContaining('week', '2026-11-22')).toEqual({ view: 'week', from: '2026-11-16', days: 7 })
  })

  it('passe à la période précédente ou suivante', () => {
    expect(shiftPeriod({ view: 'month', from: '2026-12-01', days: 31 }, 1)).toEqual({ view: 'month', from: '2027-01-01', days: 31 })
    expect(shiftPeriod({ view: 'month', from: '2026-03-01', days: 31 }, -1)).toEqual({ view: 'month', from: '2026-02-01', days: 28 })
    expect(shiftPeriod({ view: 'week', from: '2026-11-16', days: 7 }, -1).from).toBe('2026-11-09')
    expect(periodIncludes({ view: 'week', from: '2026-11-16', days: 7 }, '2026-11-22')).toBe(true)
    expect(periodIncludes({ view: 'week', from: '2026-11-16', days: 7 }, '2026-11-23')).toBe(false)
  })
})

describe('chiffres du planning', () => {
  const block = (id: number, starts_on: string, ends_on: string, type: 'booking' | 'ical' | 'manual') => ({
    id, type, starts_on, ends_on, has_conflict: false, label: null, external_url: null,
    booking: type === 'booking' ? { reference: `BT-${id}`, status: 'confirmed' as const, source: 'direct' as const, guests: 2 } : null,
  })

  it('compte nuits vendues, ouvertes, arrivées et nuits libres de week-end', () => {
    // Semaine du lundi 16 au dimanche 22 novembre 2026, deux logements = 14 nuits.
    const planning: Planning = {
      from: '2026-11-16',
      to: '2026-11-23',
      apartments: [
        { id: 1, name: 'A', blocks: [block(1, '2026-11-14', '2026-11-18', 'booking'), block(2, '2026-11-20', '2026-11-22', 'ical')] },
        { id: 2, name: 'B', blocks: [block(3, '2026-11-16', '2026-11-18', 'manual')] },
      ],
    }
    expect(planningFigures(planning, 7)).toEqual({
      soldNights: 4,
      openNights: 12,
      occupancy: 33.3,
      arrivals: 1,
      directArrivals: 0,
      freeNights: 8,
      freeWeekendNights: 2,
    })
  })

  it('abrège le nom du voyageur', () => {
    expect(shortGuestName('camille Martin')).toBe('C. Martin')
    expect(shortGuestName('Airbnb')).toBe('Airbnb')
  })
})
