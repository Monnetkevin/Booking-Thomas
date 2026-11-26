import { centsToInput, parseEuros } from '@/lib/money'
import type { IsoDay, RatePeriod, RatePeriodInput } from './types'

export const ALL_DAYS: IsoDay[] = [1, 2, 3, 4, 5, 6, 7]

export interface RateFormValues {
  label: string
  startsOn: string
  endsOn: string
  nightlyPrice: string
  minStay: string
  weekendSupplement: string
  arrivalDays: IsoDay[]
}

export type RateFormErrors = Partial<Record<keyof RateFormValues, string>>

export const emptyRateForm: RateFormValues = {
  label: '',
  startsOn: '',
  endsOn: '',
  nightlyPrice: '',
  minStay: '',
  weekendSupplement: '',
  arrivalDays: ALL_DAYS,
}

export function formFromPeriod(period: RatePeriod): RateFormValues {
  return {
    label: period.label ?? '',
    startsOn: period.starts_on,
    endsOn: period.ends_on,
    nightlyPrice: centsToInput(period.nightly_price),
    minStay: period.min_stay === null ? '' : String(period.min_stay),
    weekendSupplement: centsToInput(period.weekend_supplement),
    arrivalDays: period.arrival_days ?? ALL_DAYS,
  }
}

/** Erreurs connues du navigateur : clés de traduction (admin.rates.*). */
export function rateInputFromForm(
  values: RateFormValues,
): { input: RatePeriodInput; errors?: undefined } | { input?: undefined; errors: RateFormErrors } {
  const errors: RateFormErrors = {}
  const nightlyPrice = parseEuros(values.nightlyPrice)
  const weekend = values.weekendSupplement.trim() === '' ? null : parseEuros(values.weekendSupplement)

  if (nightlyPrice === null) errors.nightlyPrice = 'invalidAmount'
  if (values.weekendSupplement.trim() !== '' && weekend === null) errors.weekendSupplement = 'invalidAmount'
  if (values.arrivalDays.length === 0) errors.arrivalDays = 'noDay'
  if (Object.keys(errors).length > 0) return { errors }

  const days = [...values.arrivalDays].sort((a, b) => a - b)

  return {
    input: {
      label: values.label.trim() || null,
      starts_on: values.startsOn,
      ends_on: values.endsOn,
      nightly_price: nightlyPrice as number,
      min_stay: values.minStay.trim() === '' ? null : Number.parseInt(values.minStay, 10),
      weekend_supplement: weekend,
      arrival_days: days.length === 7 ? null : days,
    },
  }
}

const apiFieldMap: Record<string, keyof RateFormValues> = {
  label: 'label',
  starts_on: 'startsOn',
  ends_on: 'endsOn',
  nightly_price: 'nightlyPrice',
  min_stay: 'minStay',
  weekend_supplement: 'weekendSupplement',
  arrival_days: 'arrivalDays',
}

export function rateErrorsFromApi(fieldErrors: Record<string, string[]>): RateFormErrors {
  const errors: RateFormErrors = {}
  for (const [key, messages] of Object.entries(fieldErrors)) {
    const field = apiFieldMap[key.split('.')[0] ?? key]
    if (field && messages[0] && !errors[field]) errors[field] = messages[0]
  }
  return errors
}
