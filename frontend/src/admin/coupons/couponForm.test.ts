import { describe, expect, it } from 'vitest'
import { couponInputFromForm, emptyCouponForm, randomCode } from './couponForm'

describe('formulaire de code promo', () => {
  it('envoie le code en majuscules, un montant en centimes et des conditions facultatives', () => {
    const result = couponInputFromForm({ ...emptyCouponForm, code: ' hiver-15 ', type: 'fixed', value: '15', minNights: '2', minAmount: '', apartmentIds: [3] })
    expect(result.input).toMatchObject({ code: 'HIVER-15', target: 'stay', type: 'fixed', value: 1500, extra_id: null, min_nights: 2, min_amount: null, max_uses: null, apartment_ids: [3] })
  })

  it('vise une option pour une option offerte, et l’exige', () => {
    expect(couponInputFromForm({ ...emptyCouponForm, code: 'LOFT4', type: 'free_extra' }).errors).toEqual({ extraId: 'required' })
    expect(couponInputFromForm({ ...emptyCouponForm, code: 'LOFT4', type: 'free_extra', extraId: '7', freeQuantity: '1', automatic: true }).input).toMatchObject({
      target: 'extra', value: null, extra_id: 7, free_quantity: 1, automatic: true,
    })
  })

  it('ne limite pas une offre automatique par voyageur', () => {
    expect(couponInputFromForm({ ...emptyCouponForm, code: 'HIVER', value: '10', maxUsesPerEmail: '1', automatic: true }).input?.max_uses_per_email).toBeNull()
    expect(couponInputFromForm({ ...emptyCouponForm, code: 'HIVER', value: '10', maxUsesPerEmail: '1' }).input?.max_uses_per_email).toBe(1)
  })

  it('refuse un pourcentage hors limites, un code trop court et un nombre invalide', () => {
    expect(couponInputFromForm({ ...emptyCouponForm, code: 'AB', value: '150', maxUses: '0' }).errors).toEqual({ code: 'required', value: 'invalidPercent', maxUses: 'invalidNumber' })
  })

  it('génère un code lisible', () => {
    expect(randomCode(6, () => 0)).toBe('AAAAAA')
    expect(randomCode()).toMatch(/^[A-HJ-NP-Z2-9]{8}$/)
  })
})
