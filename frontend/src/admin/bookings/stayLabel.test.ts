import { describe, expect, it } from 'vitest'
import { stayLabel } from './stayLabel'

describe('séjour court', () => {
  it('abrège selon le mois et l’année', () => {
    expect(stayLabel('2026-11-04', '2026-11-07')).toBe('4 → 7 nov.')
    expect(stayLabel('2026-10-28', '2026-11-02')).toBe('28 oct. → 2 nov.')
    expect(stayLabel('2026-12-30', '2027-01-02')).toBe('30 déc. 2026 → 2 janv. 2027')
  })
})
