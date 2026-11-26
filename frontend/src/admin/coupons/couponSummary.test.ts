import { describe, expect, it } from 'vitest'
import i18n from '@/i18n'
import '@/admin/i18n'
import { couponStatus, couponSummary } from './couponSummary'
import type { CouponInput } from './types'

const t = i18n.getFixedT('fr')
const base: CouponInput = {
  code: 'BIENVENUE', target: 'stay', type: 'percent', value: 10, extra_id: null, free_quantity: null, starts_on: null, ends_on: null,
  max_uses: null, max_uses_per_email: null, min_nights: null, min_amount: null, automatic: false, is_active: true, apartment_ids: [],
}

describe('résumé d’un code promo', () => {
  it('écrit un code simple en clair', () => {
    expect(couponSummary(base, null, [], t)).toBe('Avec le code BIENVENUE, −10 % sur le séjour.')
  })

  it('écrit l’offre automatique de la spec, avec ses conditions et limites', () => {
    const offer = { ...base, code: 'LOFT4', automatic: true, type: 'free_extra' as const, target: 'extra' as const, free_quantity: 1, min_nights: 4, ends_on: '2027-03-31', max_uses: 50 }
    expect(couponSummary(offer, 'Champagne', ['Le Loft balnéo'], t)).toBe(
      'Sans code, automatiquement, dans Le Loft balnéo, à partir de 4 nuits, Champagne offert. Valable jusqu’au 31 mars 2027, 50 utilisations au plus.',
    )
  })
})

describe('état d’un code', () => {
  const facts = { is_active: true, starts_on: '2026-12-01', ends_on: '2026-12-24', max_uses: 30, uses: 0 }

  it('distingue programmé, en cours, épuisé, expiré et désactivé', () => {
    expect(couponStatus(facts, '2026-11-26')).toBe('scheduled')
    expect(couponStatus(facts, '2026-12-10')).toBe('active')
    expect(couponStatus({ ...facts, uses: 30 }, '2026-12-10')).toBe('exhausted')
    expect(couponStatus(facts, '2026-12-25')).toBe('expired')
    expect(couponStatus({ ...facts, is_active: false }, '2026-12-10')).toBe('disabled')
  })
})
