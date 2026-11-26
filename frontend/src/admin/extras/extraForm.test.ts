import { describe, expect, it } from 'vitest'
import { emptyExtraForm, extraInputFromForm, formFromExtra } from './extraForm'

describe('formulaire d’option', () => {
  it('envoie le prix en centimes et une quantité seulement pour une option à l’unité', () => {
    const result = extraInputFromForm({ ...emptyExtraForm, nameFr: ' Champagne ', price: '45,50', pricing: 'per_unit', maxQuantity: '3', apartmentIds: [2] })
    expect(result.input).toMatchObject({ name_fr: 'Champagne', name_en: null, price: 4550, pricing: 'per_unit', max_quantity: 3, lead_time_hours: 48, apartment_ids: [2] })

    expect(extraInputFromForm({ ...emptyExtraForm, nameFr: 'Petit-déjeuner', price: '12', pricing: 'per_guest_night', maxQuantity: 'x' }).input?.max_quantity).toBe(1)
  })

  it('accepte une option gratuite sans prix, refuse un prix vide ou nul pour une option payante', () => {
    expect(extraInputFromForm({ ...emptyExtraForm, nameFr: 'Lit bébé', free: true }).input?.price).toBe(0)
    expect(extraInputFromForm({ ...emptyExtraForm, nameFr: 'Parking', price: '' }).errors).toEqual({ price: 'invalidAmount' })
    expect(extraInputFromForm({ ...emptyExtraForm, nameFr: 'Parking', price: '0' }).errors).toEqual({ price: 'invalidAmount' })
    expect(extraInputFromForm({ ...emptyExtraForm, price: '5', leadTimeHours: '-1' }).errors).toEqual({ nameFr: 'required', leadTimeHours: 'invalidNumber' })
  })

  it('reprend une option existante', () => {
    const values = formFromExtra({
      id: 1, name_fr: 'Lit bébé', name_en: null, description_fr: null, description_en: null, price: 0, pricing: 'per_stay',
      max_quantity: 1, lead_time_hours: 24, is_active: false, apartments: [{ id: 4, name: 'Le Nid' }],
    })
    expect(values).toMatchObject({ free: true, price: '', leadTimeHours: '24', active: false, apartmentIds: [4] })
  })
})
