import { centsToInput, parseEuros } from '@/lib/money'
import type { Apartment, ApartmentInput, CancellationPolicy } from './types'

/** Valeurs du formulaire, telles que saisies (chaînes), avant conversion pour l'API. */
export interface ApartmentFormValues {
  nameFr: string
  nameEn: string
  taglineFr: string
  taglineEn: string
  descriptionFr: string
  descriptionEn: string
  address: string
  postalCode: string
  city: string
  capacity: string
  bedrooms: string
  beds: string
  bathrooms: string
  surface: string
  checkInTime: string
  checkOutTime: string
  minStay: string
  maxStay: string
  turnoverDays: string
  cleaningFee: string
  cancellationPolicy: CancellationPolicy
  registrationNumber: string
}

export const emptyApartmentForm: ApartmentFormValues = {
  nameFr: '',
  nameEn: '',
  taglineFr: '',
  taglineEn: '',
  descriptionFr: '',
  descriptionEn: '',
  address: '',
  postalCode: '72000',
  city: 'Le Mans',
  capacity: '2',
  bedrooms: '1',
  beds: '1',
  bathrooms: '1',
  surface: '',
  checkInTime: '16:00',
  checkOutTime: '10:00',
  minStay: '2',
  maxStay: '28',
  turnoverDays: '0',
  cleaningFee: '0',
  cancellationPolicy: 'moderate',
  registrationNumber: '',
}

export function formFromApartment(apartment: Apartment): ApartmentFormValues {
  const text = (value: number | null) => (value === null ? '' : String(value))

  return {
    nameFr: apartment.name.fr ?? '',
    nameEn: apartment.name.en ?? '',
    taglineFr: apartment.tagline.fr ?? '',
    taglineEn: apartment.tagline.en ?? '',
    descriptionFr: apartment.description.fr ?? '',
    descriptionEn: apartment.description.en ?? '',
    address: apartment.address,
    postalCode: apartment.postal_code,
    city: apartment.city,
    capacity: String(apartment.capacity),
    bedrooms: String(apartment.bedrooms),
    beds: String(apartment.beds),
    bathrooms: String(apartment.bathrooms),
    surface: text(apartment.surface_m2),
    checkInTime: apartment.check_in_time,
    checkOutTime: apartment.check_out_time,
    minStay: String(apartment.min_stay),
    maxStay: String(apartment.max_stay),
    turnoverDays: String(apartment.turnover_days),
    cleaningFee: centsToInput(apartment.cleaning_fee),
    cancellationPolicy: apartment.cancellation_policy,
    registrationNumber: apartment.registration_number ?? '',
  }
}

export type ApartmentFormErrors = Partial<Record<keyof ApartmentFormValues, string>>

/**
 * Convertit la saisie pour l'API. Seul ce que le navigateur sait vérifier est contrôlé
 * ici (montant lisible) ; toutes les autres règles sont celles de l'API.
 */
export function apartmentInputFromForm(
  values: ApartmentFormValues,
): { input: ApartmentInput; errors?: undefined } | { input?: undefined; errors: ApartmentFormErrors } {
  const cleaningFee = parseEuros(values.cleaningFee || '0')
  if (cleaningFee === null) return { errors: { cleaningFee: 'invalidAmount' } }

  const integer = (value: string) => Number.parseInt(value, 10)
  const optionalInteger = (value: string) => (value.trim() === '' ? null : integer(value))
  const optionalText = (value: string) => (value.trim() === '' ? null : value.trim())

  return {
    input: {
      name: { fr: values.nameFr.trim(), en: values.nameEn.trim() },
      tagline: { fr: values.taglineFr.trim(), en: values.taglineEn.trim() },
      description: { fr: values.descriptionFr.trim(), en: values.descriptionEn.trim() },
      address: values.address.trim(),
      postal_code: values.postalCode.trim(),
      city: values.city.trim(),
      capacity: integer(values.capacity),
      bedrooms: integer(values.bedrooms),
      beds: integer(values.beds),
      bathrooms: integer(values.bathrooms),
      surface_m2: optionalInteger(values.surface),
      check_in_time: values.checkInTime,
      check_out_time: values.checkOutTime,
      min_stay: integer(values.minStay),
      max_stay: integer(values.maxStay),
      turnover_days: integer(values.turnoverDays),
      cleaning_fee: cleaningFee,
      cancellation_policy: values.cancellationPolicy,
      registration_number: optionalText(values.registrationNumber),
    },
  }
}

/** Clé d'erreur de l'API (« name.fr », « cleaning_fee ») → champ du formulaire. */
const apiFieldMap: Record<string, keyof ApartmentFormValues> = {
  'name.fr': 'nameFr',
  'name.en': 'nameEn',
  'tagline.fr': 'taglineFr',
  'tagline.en': 'taglineEn',
  'description.fr': 'descriptionFr',
  'description.en': 'descriptionEn',
  address: 'address',
  postal_code: 'postalCode',
  city: 'city',
  capacity: 'capacity',
  bedrooms: 'bedrooms',
  beds: 'beds',
  bathrooms: 'bathrooms',
  surface_m2: 'surface',
  check_in_time: 'checkInTime',
  check_out_time: 'checkOutTime',
  min_stay: 'minStay',
  max_stay: 'maxStay',
  turnover_days: 'turnoverDays',
  cleaning_fee: 'cleaningFee',
  cancellation_policy: 'cancellationPolicy',
  registration_number: 'registrationNumber',
}

export function formErrorsFromApi(fieldErrors: Record<string, string[]>): ApartmentFormErrors {
  const errors: ApartmentFormErrors = {}
  for (const [key, messages] of Object.entries(fieldErrors)) {
    const field = apiFieldMap[key]
    if (field && messages[0]) errors[field] = messages[0]
  }
  return errors
}
