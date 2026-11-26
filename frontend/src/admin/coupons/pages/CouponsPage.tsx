import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { useApartmentList } from '@/admin/apartments/useApartments'
import { PageHeader } from '@/admin/components/PageHeader'
import { useExtraCatalog } from '@/admin/extras/useExtras'
import { ErrorState } from '@/components/feedback/ErrorState'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Section } from '@/components/ui/Section'
import { Spinner } from '@/components/ui/Spinner'
import { ApiError } from '@/lib/api'
import { toIso } from '@/lib/dates'
import { CouponFormDialog } from '../components/CouponFormDialog'
import { CouponTable } from '../components/CouponTable'
import { emptyCouponForm, formFromCoupon } from '../couponForm'
import { couponStatus } from '../couponSummary'
import type { Coupon } from '../types'
import { useCoupons, useSaveCoupon } from '../useCoupons'

/** Codes promo et offres automatiques : liste avec utilisations, création, modification. */
export function CouponsPage() {
  const { t } = useTranslation()
  const coupons = useCoupons()
  const apartments = useApartmentList()
  const extras = useExtraCatalog()
  const save = useSaveCoupon()
  const [editing, setEditing] = useState<Coupon | 'new' | null>(null)
  const [today] = useState(() => toIso(new Date()))
  const apartmentOptions = (apartments.data ?? []).map((apartment) => ({ id: apartment.id, name: apartment.name }))
  const extraOptions = (extras.data?.data ?? []).map((extra) => ({ id: extra.id, name: extra.name_fr }))
  const list = coupons.data ?? []
  const active = list.filter((coupon) => couponStatus(coupon, today) === 'active').length

  const close = () => {
    setEditing(null)
    save.reset()
  }

  return (
    <>
      <PageHeader
        title={t('admin.coupons.title')}
        subtitle={coupons.data ? t('admin.coupons.subtitle', { count: list.length, active }) : undefined}
        actions={<Button onClick={() => setEditing('new')}>{t('admin.coupons.new')}</Button>}
      />
      {coupons.isPending ? (
        <Spinner label={t('common.loading')} />
      ) : coupons.isError ? (
        <ErrorState onRetry={() => void coupons.refetch()} />
      ) : (
        <Section title={t('admin.coupons.title')}>
          {list.length === 0 ? <Alert tone="info">{t('admin.coupons.empty')}</Alert> : <CouponTable coupons={list} today={today} onEdit={setEditing} />}
        </Section>
      )}
      {editing !== null && (
        <CouponFormDialog
          title={t(editing === 'new' ? 'admin.coupons.new' : 'admin.coupons.edit')}
          initialValues={editing === 'new' ? emptyCouponForm : formFromCoupon(editing)}
          apartments={apartmentOptions}
          extras={extraOptions}
          pending={save.isPending}
          serverErrors={save.error instanceof ApiError ? save.error.fieldErrors : {}}
          failed={save.isError && !(save.error instanceof ApiError && save.error.status === 422)}
          onSubmit={(input) => save.mutate({ id: editing === 'new' ? null : editing.id, input }, { onSuccess: close })}
          onClose={close}
        />
      )}
    </>
  )
}
