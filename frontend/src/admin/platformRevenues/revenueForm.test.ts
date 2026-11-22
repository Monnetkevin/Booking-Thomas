import { describe, expect, it } from 'vitest'
import { defaultMonth, initialDrafts, isEditable, shiftMonth, summarize } from './revenueForm'
import type { RevenueSheet } from './types'

const sheet: RevenueSheet = {
  month: '2026-10',
  is_current: false,
  apartments: [
    { id: 1, name: 'L’élégance Mancelle', platforms: { airbnb: { nights: 26, net_amount: 155207 }, booking: { nights: 0, net_amount: null } } },
    { id: 2, name: 'Le Cocon', platforms: { airbnb: { nights: 12, net_amount: null }, booking: { nights: 6, net_amount: 46800 } } },
    { id: 3, name: 'Le Loft', platforms: { airbnb: { nights: 0, net_amount: null }, booking: { nights: 0, net_amount: 0 } } },
  ],
  totals: { airbnb: 155207, booking: 46800 },
  missing: 1,
  last_saved: null,
}

describe('saisie des revenus des plateformes', () => {
  it('décale les mois et ouvre par défaut le mois précédent', () => {
    expect(shiftMonth('2026-01', -1)).toBe('2025-12')
    expect(shiftMonth('2026-12', 1)).toBe('2027-01')
    expect(defaultMonth(new Date(2026, 10, 22))).toBe('2026-10')
    expect(defaultMonth(new Date(2027, 0, 3))).toBe('2026-12')
  })

  it('montre un champ pour les plateformes avec des nuits ou un montant', () => {
    expect(isEditable(sheet, 1, 'airbnb')).toBe(true)
    expect(isEditable(sheet, 1, 'booking')).toBe(false)
    expect(isEditable(sheet, 3, 'booking')).toBe(true)
  })

  it('reprend les montants en euros et calcule totaux, cases à saisir et net par nuit', () => {
    const drafts = initialDrafts(sheet)
    expect(drafts['1|airbnb']).toBe('1552,07')
    expect(drafts['3|booking']).toBe('0')

    const summary = summarize(sheet, drafts)
    expect(summary.missing).toBe(1)
    expect(summary.totals).toEqual({ airbnb: 155207, booking: 46800 })
    expect(summary.perNight).toEqual({ 1: 5970, 2: 7800, 3: null })
    expect(summary.entries).toContainEqual({ apartment_id: 2, platform: 'airbnb', net_amount: null })
    expect(summary.entries).not.toContainEqual(expect.objectContaining({ apartment_id: 1, platform: 'booking' }))
  })

  it('accepte « 1 386,00 », refuse une saisie qui n’est pas un montant, efface un champ vidé', () => {
    const summary = summarize(sheet, { ...initialDrafts(sheet), '2|airbnb': '1 386,00', '2|booking': 'abc', '1|airbnb': '' })

    expect(summary.invalid).toEqual(['2|booking'])
    expect(summary.entries).toContainEqual({ apartment_id: 2, platform: 'airbnb', net_amount: 138600 })
    expect(summary.entries).toContainEqual({ apartment_id: 1, platform: 'airbnb', net_amount: null })
    expect(summary.missing).toBe(1)
    expect(summary.perNight[2]).toBe(11550)
  })
})
