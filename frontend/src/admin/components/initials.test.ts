import { describe, expect, it } from 'vitest'
import { initials } from './initials'

describe('initiales', () => {
  it('prend la première lettre du prénom et du nom', () => {
    expect(initials('Thomas Hôte')).toBe('TH')
    expect(initials('  alice  ')).toBe('A')
    expect(initials('Jean Pierre Martin')).toBe('JM')
    expect(initials('')).toBe('')
  })
})
