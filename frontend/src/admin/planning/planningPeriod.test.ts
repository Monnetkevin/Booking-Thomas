import { describe, expect, it } from 'vitest'
import { shortGuestName } from './planningFigures'
import { periodContaining, periodIncludes, shiftPeriod } from './planningPeriod'

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

describe('nom abrégé', () => {
  it('abrège le nom du voyageur', () => {
    expect(shortGuestName('camille Martin')).toBe('C. Martin')
    expect(shortGuestName('Airbnb')).toBe('Airbnb')
  })
})
