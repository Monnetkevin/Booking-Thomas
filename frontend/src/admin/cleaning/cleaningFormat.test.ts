import { describe, expect, it } from 'vitest'
import { formatHour, isLeaving, relativeDay } from './cleaningFormat'

describe('mise en forme du planning du ménage', () => {
  it('écrit les heures à la française', () => {
    expect(formatHour('16:00')).toBe('16 h')
    expect(formatHour('10:30')).toBe('10 h 30')
    expect(formatHour('09:05')).toBe('9 h 05')
  })

  it('nomme aujourd’hui et demain, y compris en fin de mois', () => {
    expect(relativeDay('2026-11-30', '2026-11-30')).toBe('today')
    expect(relativeDay('2026-12-01', '2026-11-30')).toBe('tomorrow')
    expect(relativeDay('2026-12-02', '2026-11-30')).toBe('later')
  })

  it('distingue ce qui libère le logement de ce qui l’occupe', () => {
    expect(isLeaving('departure')).toBe(true)
    expect(isLeaving('occupied_end')).toBe(true)
    expect(isLeaving('arrival')).toBe(false)
    expect(isLeaving('blocked')).toBe(false)
  })
})
