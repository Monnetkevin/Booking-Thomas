import { describe, expect, it } from 'vitest'
import { addDays, stayCalendarRules } from './stayCalendar'
import type { AvailabilityCalendar } from './types'

const calendar: AvailabilityCalendar = {
  from: '2026-11-10',
  to: '2027-11-11',
  first_arrival: '2026-11-11',
  last_arrival: '2027-11-10',
  min_stay: 2,
  max_stay: 7,
  // Séjour réservé du 20 au 23 (nuits 20, 21, 22) ; aucune période après le 31/12.
  unavailable: ['2026-11-20', '2026-11-21', '2026-11-22'],
  periods: [
    { starts_on: '2026-11-01', ends_on: '2026-11-30', min_stay: 2, arrival_days: null },
    // Décembre : 3 nuits minimum, arrivée le vendredi (5) ou le samedi (6).
    { starts_on: '2026-12-01', ends_on: '2027-12-31', min_stay: 3, arrival_days: [5, 6] },
  ],
}

const rules = stayCalendarRules(calendar)

describe('calendrier de séjour', () => {
  it('ajoute des jours sans décalage de fuseau', () => {
    expect(addDays('2027-03-27', 2)).toBe('2027-03-29')
    expect(addDays('2026-12-31', 1)).toBe('2027-01-01')
  })

  it('refuse une arrivée avant le préavis, sur une nuit prise ou sans assez de nuits libres', () => {
    expect(rules.canArrive('2026-11-10')).toBe(false)
    expect(rules.canArrive('2026-11-11')).toBe(true)
    expect(rules.canArrive('2026-11-21')).toBe(false)
    // Le 19 : une seule nuit libre avant le séjour réservé, minimum 2.
    expect(rules.canArrive('2026-11-19')).toBe(false)
    expect(rules.canArrive('2026-11-18')).toBe(true)
  })

  it('accepte une arrivée le jour d’un départ', () => {
    expect(rules.canArrive('2026-11-23')).toBe(true)
  })

  it('applique les jours d’arrivée et le séjour minimum de la période', () => {
    expect(rules.canArrive('2026-12-04')).toBe(true) // vendredi
    expect(rules.canArrive('2026-12-07')).toBe(false) // lundi
    expect(rules.minStayFor('2026-12-04')).toBe(3)
    expect(rules.canDepart('2026-12-04', '2026-12-06')).toBe(false)
    expect(rules.canDepart('2026-12-04', '2026-12-07')).toBe(true)
  })

  it('permet de partir le jour où une nuit est prise, pas après', () => {
    expect(rules.canDepart('2026-11-18', '2026-11-20')).toBe(true)
    expect(rules.canDepart('2026-11-18', '2026-11-21')).toBe(false)
  })

  it('respecte le séjour maximum', () => {
    expect(rules.canDepart('2026-11-23', '2026-11-30')).toBe(true)
    expect(rules.canDepart('2026-11-23', '2026-12-01')).toBe(false)
  })
})
