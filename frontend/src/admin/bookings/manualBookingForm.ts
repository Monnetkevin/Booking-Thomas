import { parseEuros } from '@/lib/money'
import type { ManualBookingInput } from './types'

/** Saisie du formulaire « Saisir une réservation », telle que tapée. */
export interface ManualBookingFormValues {
  apartmentId: string
  arrival: string
  departure: string
  adults: string
  children: string
  guestName: string
  guestEmail: string
  guestPhone: string
  locale: 'fr' | 'en'
  housekeepingNotes: string
  /** Total en euros, vide pour garder le prix calculé. */
  total: string
  paid: boolean
  sendConfirmation: boolean
}

export type ManualBookingFormErrors = Partial<Record<keyof ManualBookingFormValues, 'required' | 'invalidNumber' | 'invalidAmount'>>

export const emptyManualBooking: ManualBookingFormValues = {
  apartmentId: '',
  arrival: '',
  departure: '',
  adults: '2',
  children: '0',
  guestName: '',
  guestEmail: '',
  guestPhone: '',
  locale: 'fr',
  housekeepingNotes: '',
  total: '',
  paid: false,
  sendConfirmation: true,
}

const optional = (value: string) => (value.trim() === '' ? null : value.trim())

/** Valeurs envoyées à l'API, ou les erreurs repérées dans le navigateur (clés admin.manual.errors.*). */
export function manualBookingInput(
  values: ManualBookingFormValues,
): { input: ManualBookingInput; errors?: undefined } | { input?: undefined; errors: ManualBookingFormErrors } {
  const errors: ManualBookingFormErrors = {}
  const required: Array<keyof ManualBookingFormValues> = ['apartmentId', 'arrival', 'departure', 'guestName']
  for (const field of required) if (String(values[field]).trim() === '') errors[field] = 'required'
  const adults = /^\d+$/.test(values.adults) ? Number(values.adults) : null
  const children = /^\d+$/.test(values.children || '0') ? Number(values.children || '0') : null
  if (adults === null || adults < 1) errors.adults = 'invalidNumber'
  if (children === null) errors.children = 'invalidNumber'
  const total = values.total.trim() === '' ? null : parseEuros(values.total)
  if (values.total.trim() !== '' && total === null) errors.total = 'invalidAmount'
  if (Object.keys(errors).length > 0) return { errors }

  return {
    input: {
      apartment_id: Number(values.apartmentId),
      arrival: values.arrival,
      departure: values.departure,
      adults: adults as number,
      children: children as number,
      guest_name: values.guestName.trim(),
      guest_email: optional(values.guestEmail),
      guest_phone: optional(values.guestPhone),
      locale: values.locale,
      housekeeping_notes: optional(values.housekeepingNotes),
      total_amount: total,
      paid: values.paid,
      // Sans e-mail, il n'y a rien à envoyer.
      send_confirmation: values.sendConfirmation && optional(values.guestEmail) !== null,
    },
  }
}
