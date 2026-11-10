import { describe, expect, it } from 'vitest'
import { emptyRateForm, formFromPeriod, rateErrorsFromApi, rateInputFromForm } from './rateForm'
import type { RatePeriod } from './types'

const period: RatePeriod = {
  id: 3,
  apartment_id: 1,
  label: 'Noël',
  starts_on: '2026-12-20',
  ends_on: '2027-01-02',
  nightly_price: 11950,
  min_stay: 3,
  weekend_supplement: null,
  arrival_days: [5, 6],
}

describe('formulaire de période tarifaire', () => {
  it('fait l’aller-retour sans perte', () => {
    expect(rateInputFromForm(formFromPeriod(period)).input).toEqual({
      label: 'Noël',
      starts_on: '2026-12-20',
      ends_on: '2027-01-02',
      nightly_price: 11950,
      min_stay: 3,
      weekend_supplement: null,
      arrival_days: [5, 6],
    })
  })

  it('envoie « tous les jours » comme aucune restriction et un nom vide comme null', () => {
    const { input } = rateInputFromForm({ ...emptyRateForm, startsOn: '2027-01-01', endsOn: '2027-01-31', nightlyPrice: '80' })
    expect(input).toMatchObject({ label: null, arrival_days: null, min_stay: null, nightly_price: 8000 })
  })

  it('signale un prix illisible et l’absence de jour d’arrivée', () => {
    expect(rateInputFromForm({ ...emptyRateForm, nightlyPrice: 'cher', arrivalDays: [] }).errors).toEqual({
      nightlyPrice: 'invalidAmount',
      arrivalDays: 'noDay',
    })
  })

  it('rattache les erreurs de l’API, y compris celles des jours', () => {
    expect(rateErrorsFromApi({ starts_on: ['Chevauche « Noël »'], 'arrival_days.0': ['Jour invalide'] })).toEqual({
      startsOn: 'Chevauche « Noël »',
      arrivalDays: 'Jour invalide',
    })
  })
})
