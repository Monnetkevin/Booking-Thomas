import { describe, expect, it } from 'vitest'
import { centsToInput, formatEuros, parseEuros } from './money'

const nbsp = (text: string) => text.replace(/[  ]/g, ' ')

describe('montants', () => {
  it('affiche les centimes en euros, sans décimales inutiles', () => {
    expect(nbsp(formatEuros(9500))).toBe('95 €')
    expect(nbsp(formatEuros(9550))).toBe('95,50 €')
    expect(nbsp(formatEuros(120000))).toBe('1 200 €')
  })

  it('prépare la valeur d’un champ', () => {
    expect(centsToInput(9500)).toBe('95')
    expect(centsToInput(9505)).toBe('95,05')
    expect(centsToInput(null)).toBe('')
  })

  it('lit une saisie en euros', () => {
    expect(parseEuros('95')).toBe(9500)
    expect(parseEuros('95,5')).toBe(9550)
    expect(parseEuros('95.50 €')).toBe(9550)
    expect(parseEuros('1 200')).toBe(120000)
    expect(parseEuros('0,1')).toBe(10)
    expect(parseEuros('douze')).toBeNull()
    expect(parseEuros('-5')).toBeNull()
    expect(parseEuros('9,999')).toBeNull()
  })
})
