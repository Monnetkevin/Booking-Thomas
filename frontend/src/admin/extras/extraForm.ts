import { centsToInput, parseEuros } from '@/lib/money'
import type { Extra, ExtraInput, ExtraPricing } from './types'

/** Saisie du formulaire d'option, telle que tapée. */
export interface ExtraFormValues {
  nameFr: string
  nameEn: string
  descriptionFr: string
  descriptionEn: string
  /** Prix en euros ; ignoré pour une option gratuite. */
  price: string
  free: boolean
  pricing: ExtraPricing
  maxQuantity: string
  leadTimeHours: string
  active: boolean
  apartmentIds: number[]
}

export type ExtraFormErrors = Partial<Record<keyof ExtraFormValues, 'required' | 'invalidAmount' | 'invalidNumber'>>

export const emptyExtraForm: ExtraFormValues = {
  nameFr: '',
  nameEn: '',
  descriptionFr: '',
  descriptionEn: '',
  price: '',
  free: false,
  pricing: 'per_stay',
  maxQuantity: '1',
  leadTimeHours: '48',
  active: true,
  apartmentIds: [],
}

export function formFromExtra(extra: Extra): ExtraFormValues {
  return {
    nameFr: extra.name_fr,
    nameEn: extra.name_en ?? '',
    descriptionFr: extra.description_fr ?? '',
    descriptionEn: extra.description_en ?? '',
    price: extra.price === 0 ? '' : centsToInput(extra.price),
    free: extra.price === 0,
    pricing: extra.pricing,
    maxQuantity: String(extra.max_quantity),
    leadTimeHours: String(extra.lead_time_hours),
    active: extra.is_active,
    apartmentIds: extra.apartments.map((apartment) => apartment.id),
  }
}

const whole = (value: string, min: number, max: number) => {
  const number = /^\d+$/.test(value.trim()) ? Number(value) : null
  return number !== null && number >= min && number <= max ? number : null
}

/** Valeurs envoyées à l'API, ou les erreurs repérées dans le navigateur. */
export function extraInputFromForm(values: ExtraFormValues): { input: ExtraInput; errors?: undefined } | { input?: undefined; errors: ExtraFormErrors } {
  const errors: ExtraFormErrors = {}
  const price = values.free ? 0 : parseEuros(values.price)
  const maxQuantity = values.pricing === 'per_unit' ? whole(values.maxQuantity, 1, 99) : 1
  const leadTime = whole(values.leadTimeHours, 0, 720)

  if (values.nameFr.trim() === '') errors.nameFr = 'required'
  if (price === null || price <= 0 === !values.free) errors.price = 'invalidAmount'
  if (maxQuantity === null) errors.maxQuantity = 'invalidNumber'
  if (leadTime === null) errors.leadTimeHours = 'invalidNumber'
  if (Object.keys(errors).length > 0) return { errors }

  return {
    input: {
      name_fr: values.nameFr.trim(),
      name_en: values.nameEn.trim() || null,
      description_fr: values.descriptionFr.trim() || null,
      description_en: values.descriptionEn.trim() || null,
      price: price as number,
      pricing: values.pricing,
      max_quantity: maxQuantity as number,
      lead_time_hours: leadTime as number,
      is_active: values.active,
      apartment_ids: values.apartmentIds,
    },
  }
}
