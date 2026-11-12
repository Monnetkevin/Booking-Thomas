import { describe, expect, it } from 'vitest'
import { formatDate, nightsBetween, formatLongDate, fromIso, toIso } from './dates'

describe('dates', () => {
  it('affiche une date courte en français, sans décalage de fuseau', () => {
    expect(formatDate('2026-12-01')).toBe('1 déc. 2026')
    expect(formatDate('2027-01-31')).toBe('31 janv. 2027')
  })

  it('compte les nuits, passage à l’heure d’été compris', () => {
    expect(nightsBetween('2026-12-01', '2026-12-04')).toBe(3)
    expect(nightsBetween('2027-03-27', '2027-03-30')).toBe(3)
  })

  it('convertit une date du calendrier en « AAAA-MM-JJ » et inversement, sans décalage de fuseau', () => {
    expect(toIso(new Date(2026, 11, 4))).toBe('2026-12-04')
    expect(toIso(fromIso('2027-01-31'))).toBe('2027-01-31')
  })

  it('écrit une date longue avec le jour de la semaine', () => {
    expect(formatLongDate('2026-11-17')).toBe('mar. 17 nov. 2026')
    expect(formatLongDate('2026-11-17', 'en-GB')).toBe('Tue, 17 Nov 2026')
  })
})
