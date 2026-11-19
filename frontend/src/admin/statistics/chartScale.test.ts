import { describe, expect, it } from 'vitest'
import { monthLabel, niceTicks } from './chartScale'

describe('échelle du graphique', () => {
  it('donne des graduations rondes qui couvrent le maximum', () => {
    expect(niceTicks(37)).toEqual([0, 10, 20, 30, 40])
    expect(niceTicks(8)).toEqual([0, 2, 4, 6, 8])
    expect(niceTicks(130)).toEqual([0, 50, 100, 150])
    expect(niceTicks(0)).toEqual([0, 1])
  })

  it('écrit les mois en français', () => {
    expect(monthLabel('2026-10', 'long')).toBe('octobre 2026')
    expect(monthLabel('2026-02')).toContain('févr.')
  })
})
