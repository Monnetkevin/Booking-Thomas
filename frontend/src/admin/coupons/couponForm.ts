import { centsToInput, parseEuros } from '@/lib/money'
import type { Coupon, CouponInput, CouponTarget, CouponType } from './types'

/** Saisie du formulaire de code, telle que tapée (montants en euros, nombres en texte). */
export interface CouponFormValues {
  code: string
  target: CouponTarget
  type: CouponType
  /** Pourcentage ou montant en euros selon le type. */
  value: string
  extraId: string
  freeQuantity: string
  startsOn: string
  endsOn: string
  maxUses: string
  maxUsesPerEmail: string
  minNights: string
  minAmount: string
  automatic: boolean
  active: boolean
  apartmentIds: number[]
}

export type CouponFormErrors = Partial<Record<keyof CouponFormValues, 'required' | 'invalidNumber' | 'invalidAmount' | 'invalidPercent'>>

export const emptyCouponForm: CouponFormValues = {
  code: '',
  target: 'stay',
  type: 'percent',
  value: '',
  extraId: '',
  freeQuantity: '1',
  startsOn: '',
  endsOn: '',
  maxUses: '',
  maxUsesPerEmail: '',
  minNights: '',
  minAmount: '',
  automatic: false,
  active: true,
  apartmentIds: [],
}

const text = (value: number | null) => (value === null ? '' : String(value))

export function formFromCoupon(coupon: Coupon): CouponFormValues {
  return {
    code: coupon.code,
    target: coupon.target,
    type: coupon.type,
    value: coupon.type === 'fixed' ? centsToInput(coupon.value) : coupon.type === 'percent' ? String(coupon.value) : '',
    extraId: coupon.extra ? String(coupon.extra.id) : '',
    freeQuantity: String(coupon.free_quantity),
    startsOn: coupon.starts_on ?? '',
    endsOn: coupon.ends_on ?? '',
    maxUses: text(coupon.max_uses),
    maxUsesPerEmail: text(coupon.max_uses_per_email),
    minNights: text(coupon.min_nights),
    minAmount: coupon.min_amount === null ? '' : centsToInput(coupon.min_amount),
    automatic: coupon.automatic,
    active: coupon.is_active,
    apartmentIds: coupon.apartments.map((apartment) => apartment.id),
  }
}

/** Code lisible au hasard (sans 0/O ni 1/I), pour le bouton « Générer ». */
export function randomCode(length = 8, random: () => number = Math.random): string {
  const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'
  return Array.from({ length }, () => alphabet[Math.floor(random() * alphabet.length)]).join('')
}

/** Valeurs envoyées à l'API, ou les erreurs repérées dans le navigateur. */
export function couponInputFromForm(values: CouponFormValues): { input: CouponInput; errors?: undefined } | { input?: undefined; errors: CouponFormErrors } {
  const errors: CouponFormErrors = {}
  const optionalWhole = (field: keyof CouponFormValues) => {
    const raw = String(values[field]).trim()
    if (raw === '') return null
    if (!/^\d+$/.test(raw) || Number(raw) < 1) errors[field] = 'invalidNumber'
    return Number(raw)
  }

  const code = values.code.trim().toUpperCase()
  if (!/^[A-Z0-9_-]{3,40}$/.test(code)) errors.code = 'required'
  const freeExtra = values.type === 'free_extra'
  const target: CouponTarget = freeExtra ? 'extra' : values.target
  let value: number | null = null
  if (values.type === 'percent') {
    value = /^\d+$/.test(values.value.trim()) ? Number(values.value) : null
    if (value === null || value < 1 || value > 100) errors.value = 'invalidPercent'
  } else if (values.type === 'fixed') {
    value = parseEuros(values.value)
    if (value === null || value <= 0) errors.value = 'invalidAmount'
  }
  if (target !== 'stay' && values.extraId === '') errors.extraId = 'required'
  const freeQuantity = freeExtra ? optionalWhole('freeQuantity') : null
  const minAmount = values.minAmount.trim() === '' ? null : parseEuros(values.minAmount)
  if (values.minAmount.trim() !== '' && (minAmount === null || minAmount <= 0)) errors.minAmount = 'invalidAmount'
  const maxUses = optionalWhole('maxUses')
  // Limite par voyageur : codes saisis seulement (l'e-mail n'est pas connu au devis d'une offre automatique).
  const maxUsesPerEmail = values.automatic ? null : optionalWhole('maxUsesPerEmail')
  const minNights = optionalWhole('minNights')
  if (Object.keys(errors).length > 0) return { errors }

  return {
    input: {
      code,
      target,
      type: values.type,
      value,
      extra_id: target === 'stay' ? null : Number(values.extraId),
      free_quantity: freeQuantity,
      starts_on: values.startsOn || null,
      ends_on: values.endsOn || null,
      max_uses: maxUses,
      max_uses_per_email: maxUsesPerEmail,
      min_nights: minNights,
      min_amount: minAmount,
      automatic: values.automatic,
      is_active: values.active,
      apartment_ids: values.apartmentIds,
    },
  }
}
