import type { TFunction } from 'i18next'
import { formatDate } from '@/lib/dates'
import { formatEuros } from '@/lib/money'
import type { CouponInput } from './types'

export type CouponStatus = 'active' | 'scheduled' | 'expired' | 'exhausted' | 'disabled'

type CouponFacts = Pick<CouponInput, 'is_active' | 'starts_on' | 'ends_on' | 'max_uses'> & { uses: number }

/** État d'un code aujourd'hui : désactivé, programmé, expiré, épuisé ou en cours. */
export function couponStatus(coupon: CouponFacts, today: string): CouponStatus {
  if (!coupon.is_active) return 'disabled'
  if (coupon.starts_on !== null && today < coupon.starts_on) return 'scheduled'
  if (coupon.ends_on !== null && today > coupon.ends_on) return 'expired'
  if (coupon.max_uses !== null && coupon.uses >= coupon.max_uses) return 'exhausted'
  return 'active'
}

/** Ce que donne le code : « −10 % sur le séjour », « 1 × Champagne offert ». */
export function couponOffer(coupon: Pick<CouponInput, 'type' | 'target' | 'value' | 'free_quantity'>, extraName: string | null, t: TFunction): string {
  if (coupon.type === 'free_extra') return t('admin.coupons.offer.free', { count: coupon.free_quantity ?? 1, extra: extraName ?? '?' })
  const amount = coupon.type === 'percent' ? `${coupon.value ?? 0} %` : formatEuros(coupon.value ?? 0)
  return t(`admin.coupons.offer.${coupon.target}`, { amount, extra: extraName ?? '?' })
}

/**
 * Résumé en clair d'un code, d'après ses réglages : « Avec le code BIENVENUE, à partir de
 * 2 nuits, −10 % sur le séjour. Valable du 1 déc. 2026 au 31 mars 2027, 50 utilisations
 * au plus, une par voyageur. »
 */
export function couponSummary(coupon: CouponInput, extraName: string | null, apartmentNames: string[], t: TFunction): string {
  const conditions = [
    apartmentNames.length > 0 ? t('admin.coupons.summary.apartments', { list: apartmentNames.join(', ') }) : null,
    coupon.min_nights ? t('admin.coupons.summary.minNights', { count: coupon.min_nights }) : null,
    coupon.min_amount ? t('admin.coupons.summary.minAmount', { amount: formatEuros(coupon.min_amount) }) : null,
  ].filter(Boolean)
  const limits = [
    coupon.starts_on && coupon.ends_on
      ? t('admin.coupons.summary.between', { from: formatDate(coupon.starts_on), to: formatDate(coupon.ends_on) })
      : coupon.starts_on
        ? t('admin.coupons.summary.from', { from: formatDate(coupon.starts_on) })
        : coupon.ends_on
          ? t('admin.coupons.summary.until', { to: formatDate(coupon.ends_on) })
          : null,
    coupon.max_uses ? t('admin.coupons.summary.maxUses', { count: coupon.max_uses }) : null,
    coupon.max_uses_per_email ? t('admin.coupons.summary.perGuest', { count: coupon.max_uses_per_email }) : null,
  ].filter(Boolean)

  const opening = coupon.automatic ? t('admin.coupons.summary.automatic') : t('admin.coupons.summary.withCode', { code: coupon.code || '…' })
  const sentence = [opening, ...conditions].join(', ')
  return [`${sentence}, ${couponOffer(coupon, extraName, t)}.`, limits.length > 0 ? `${capitalize(limits.join(', '))}.` : ''].join(' ').trim()
}

const capitalize = (text: string) => text.charAt(0).toUpperCase() + text.slice(1)
