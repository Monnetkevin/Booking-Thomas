import { useTranslation } from 'react-i18next'
import { Badge, type BadgeTone } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { formatDate } from '@/lib/dates'
import { formatEuros } from '@/lib/money'
import { couponOffer, couponStatus, type CouponStatus } from '../couponSummary'
import type { Coupon } from '../types'

const tones: Record<CouponStatus, BadgeTone> = { active: 'success', scheduled: 'accent', expired: 'neutral', exhausted: 'warning', disabled: 'neutral' }

/** Codes et offres : offre, conditions, mode, utilisations, montant remisé, état. */
export function CouponTable({ coupons, today, onEdit }: { coupons: Coupon[]; today: string; onEdit: (coupon: Coupon) => void }) {
  const { t } = useTranslation()

  const conditions = (coupon: Coupon) =>
    [
      coupon.apartments.length > 0 ? coupon.apartments.map((apartment) => apartment.name).join(', ') : t('admin.coupons.allApartments'),
      coupon.min_nights ? t('admin.coupons.summary.minNights', { count: coupon.min_nights }) : null,
      coupon.ends_on ? t('admin.coupons.summary.until', { to: formatDate(coupon.ends_on) }) : null,
    ]
      .filter(Boolean)
      .join(' · ')

  return (
    <div className="overflow-x-auto">
      <table className="w-full min-w-[760px] text-left text-sm">
        <thead>
          <tr className="border-b border-border text-xs font-bold tracking-wide text-muted uppercase">
            {(['code', 'offer', 'conditions', 'mode', 'uses', 'discount', 'status'] as const).map((column) => (
              <th key={column} scope="col" className="py-2 pr-4">
                {t(`admin.coupons.columns.${column}`)}
              </th>
            ))}
            <th scope="col" className="py-2">
              <span className="sr-only">{t('admin.common.edit')}</span>
            </th>
          </tr>
        </thead>
        <tbody className="divide-y divide-border">
          {coupons.map((coupon) => {
            const status = couponStatus(coupon, today)
            return (
              <tr key={coupon.id}>
                <td className="py-3 pr-4 font-mono font-semibold">{coupon.code}</td>
                <td className="py-3 pr-4">{couponOffer(coupon, coupon.extra?.name ?? null, t)}</td>
                <td className="py-3 pr-4 text-muted">{conditions(coupon)}</td>
                <td className="py-3 pr-4">{t(coupon.automatic ? 'admin.coupons.mode.automatic' : 'admin.coupons.mode.code')}</td>
                <td className="py-3 pr-4 tabular-nums">
                  {coupon.uses} / {coupon.max_uses ?? t('admin.coupons.unlimited')}
                </td>
                <td className="py-3 pr-4 tabular-nums">{formatEuros(coupon.discount_total)}</td>
                <td className="py-3 pr-4">
                  <Badge tone={tones[status]}>{t(`admin.coupons.status.${status}`)}</Badge>
                </td>
                <td className="py-3 text-right">
                  <Button variant="ghost" onClick={() => onEdit(coupon)}>
                    {t('admin.common.edit')}
                  </Button>
                </td>
              </tr>
            )
          })}
        </tbody>
      </table>
    </div>
  )
}
