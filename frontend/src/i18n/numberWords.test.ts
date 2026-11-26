import { describe, expect, it } from 'vitest'
import { countInWords } from './numberWords'

const words = ['zéro', 'un', 'deux', 'trois', 'quatre', 'cinq']

describe('nombres en lettres', () => {
  it('écrit le nombre en lettres, avec une majuscule pour un début de phrase', () => {
    expect(countInWords(5, words)).toEqual({ word: 'cinq', capitalized: 'Cinq' })
  })

  it('passe aux chiffres au-delà de la liste', () => {
    expect(countInWords(14, words)).toEqual({ word: '14', capitalized: '14' })
  })

  it('ne compte rien pour zéro ou un nombre invalide', () => {
    expect(countInWords(0, words)).toBeNull()
    expect(countInWords(-1, words)).toBeNull()
    expect(countInWords(2.5, words)).toBeNull()
  })
})
