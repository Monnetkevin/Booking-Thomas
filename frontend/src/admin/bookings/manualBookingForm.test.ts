import { describe, expect, it } from 'vitest'
import { emptyManualBooking, manualBookingInput } from './manualBookingForm'

const filled = { ...emptyManualBooking, apartmentId: '3', arrival: '2026-12-01', departure: '2026-12-04', guestName: ' Paul Durand ' }

describe('saisie d’une réservation à la main', () => {
  it('garde le prix calculé sans total, et convertit un total en centimes', () => {
    expect(manualBookingInput(filled).input).toMatchObject({ apartment_id: 3, adults: 2, guest_name: 'Paul Durand', total_amount: null, guest_email: null })
    expect(manualBookingInput({ ...filled, total: '450,50' }).input?.total_amount).toBe(45050)
  })

  it('n’envoie pas de confirmation sans e-mail', () => {
    expect(manualBookingInput(filled).input?.send_confirmation).toBe(false)
    expect(manualBookingInput({ ...filled, guestEmail: 'paul@example.com' }).input?.send_confirmation).toBe(true)
  })

  it('signale les champs manquants et les saisies invalides', () => {
    expect(manualBookingInput({ ...emptyManualBooking, adults: '0', total: 'beaucoup' }).errors).toEqual({
      apartmentId: 'required',
      arrival: 'required',
      departure: 'required',
      guestName: 'required',
      adults: 'invalidNumber',
      total: 'invalidAmount',
    })
  })
})
