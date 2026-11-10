import { describe, expect, it } from 'vitest'
import { searchFromParams, searchToParams, stayFromParams, stayToParams } from './searchParams'

describe('paramètres d’URL', () => {
  it('lit une recherche complète et ignore une recherche incohérente', () => {
    expect(searchFromParams(new URLSearchParams('arrivee=2026-12-04&depart=2026-12-07&voyageurs=3'))).toEqual({
      arrival: '2026-12-04',
      departure: '2026-12-07',
      guests: 3,
    })
    expect(searchFromParams(new URLSearchParams('arrivee=2026-12-07&depart=2026-12-04'))).toBeNull()
    expect(searchFromParams(new URLSearchParams('arrivee=demain&depart=2026-12-04'))).toBeNull()
  })

  it('borne le nombre de voyageurs', () => {
    expect(searchFromParams(new URLSearchParams('arrivee=2026-12-04&depart=2026-12-07&voyageurs=99'))?.guests).toBe(20)
  })

  it('fait l’aller-retour', () => {
    const criteria = { arrival: '2026-12-04', departure: '2026-12-07', guests: 2 }
    expect(searchFromParams(searchToParams(criteria))).toEqual(criteria)
  })

  it('reprend les voyageurs de la recherche comme adultes sur la fiche', () => {
    expect(stayFromParams(new URLSearchParams('arrivee=2026-12-04&depart=2026-12-07&voyageurs=3'))).toEqual({
      arrival: '2026-12-04',
      departure: '2026-12-07',
      adults: 3,
      children: 0,
    })
    expect(stayToParams({ arrival: '2026-12-04', departure: null, adults: 2, children: 1 }).toString()).toBe(
      'arrivee=2026-12-04&adultes=2&enfants=1',
    )
  })
})
