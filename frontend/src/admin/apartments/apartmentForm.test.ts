import { describe, expect, it } from 'vitest'
import { apartmentInputFromForm, emptyApartmentForm, formErrorsFromApi, formFromApartment } from './apartmentForm'
import type { Apartment } from './types'

const apartment: Apartment = {
  id: 1,
  slug: 'le-cocon',
  status: 'draft',
  name: { fr: 'Le Cocon', en: 'The Cocoon' },
  description: { fr: 'Bohème' },
  address: '12 rue de la Paix',
  postal_code: '72000',
  city: 'Le Mans',
  latitude: 48.0061,
  longitude: 0.1996,
  geocoding: 'found',
  capacity: 4,
  bedrooms: 1,
  beds: 2,
  bathrooms: 1,
  surface_m2: 60,
  check_in_time: '16:00',
  check_out_time: '10:00',
  min_stay: 2,
  max_stay: 28,
  turnover_days: 0,
  cleaning_fee: 4550,
  cancellation_policy: 'moderate',
  registration_number: null,
  position: 0,
  amenity_ids: [],
  photos: [],
  base_price: null,
  updated_at: null,
}

describe('formulaire de logement', () => {
  it('fait l’aller-retour API → formulaire → API sans perte', () => {
    const result = apartmentInputFromForm(formFromApartment(apartment))

    expect(result.errors).toBeUndefined()
    expect(result.input).toMatchObject({
      name: { fr: 'Le Cocon', en: 'The Cocoon' },
      description: { fr: 'Bohème', en: '' },
      capacity: 4,
      surface_m2: 60,
      cleaning_fee: 4550,
      registration_number: null,
    })
  })

  it('affiche les frais de ménage en euros', () => {
    expect(formFromApartment(apartment).cleaningFee).toBe('45,50')
  })

  it('signale un montant illisible sans appeler l’API', () => {
    expect(apartmentInputFromForm({ ...emptyApartmentForm, cleaningFee: 'beaucoup' }).errors).toEqual({
      cleaningFee: 'invalidAmount',
    })
  })

  it('n’envoie ni adresse de page ni coordonnées : l’API les calcule', () => {
    const { input } = apartmentInputFromForm({ ...emptyApartmentForm, nameFr: 'Nid doux' })
    expect(input).not.toHaveProperty('slug')
    expect(input).not.toHaveProperty('latitude')
    expect(input).not.toHaveProperty('longitude')
  })

  it('rattache les erreurs de l’API aux champs', () => {
    expect(formErrorsFromApi({ 'name.fr': ['Obligatoire'], cleaning_fee: ['Trop élevé'], inconnu: ['x'] })).toEqual({
      nameFr: 'Obligatoire',
      cleaningFee: 'Trop élevé',
    })
  })
})
