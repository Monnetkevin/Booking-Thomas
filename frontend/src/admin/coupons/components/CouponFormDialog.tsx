import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/components/ui/Button'
import { Dialog } from '@/components/ui/Dialog'
import { Checkbox, DateField, SelectField, TextField } from '@/components/ui/Field'
import { SegmentedControl } from '@/components/ui/SegmentedControl'
import { couponInputFromForm, randomCode, type CouponFormErrors, type CouponFormValues } from '../couponForm'
import { couponSummary } from '../couponSummary'
import type { CouponInput, CouponTarget, CouponType } from '../types'

interface CouponFormDialogProps {
  title: string
  initialValues: CouponFormValues
  apartments: Array<{ id: number; name: string }>
  extras: Array<{ id: number; name: string }>
  pending: boolean
  /** Erreurs renvoyées par l'API (code déjà pris…), par champ de l'API. */
  serverErrors: Record<string, string[] | undefined>
  failed: boolean
  onSubmit: (input: CouponInput) => void
  onClose: () => void
}

const TARGETS: CouponTarget[] = ['stay', 'extra', 'both']
const TYPES: CouponType[] = ['percent', 'fixed', 'free_extra']

/** Création ou modification d'un code ou d'une offre, avec son résumé en clair. */
export function CouponFormDialog({ title, initialValues, apartments, extras, pending, serverErrors, failed, onSubmit, onClose }: CouponFormDialogProps) {
  const { t } = useTranslation()
  const [values, setValues] = useState(initialValues)
  const [errors, setErrors] = useState<CouponFormErrors>({})
  const formId = 'coupon-formulaire'

  const update = <K extends keyof CouponFormValues>(field: K, value: CouponFormValues[K]) => {
    setValues((current) => ({ ...current, [field]: value }))
    setErrors(({ [field]: _removed, ...rest }) => rest)
  }
  const message = (field: keyof CouponFormValues, server: string) => (errors[field] ? t(`admin.coupons.errors.${errors[field]}`) : serverErrors[server]?.[0])
  const text = (field: 'code' | 'value' | 'freeQuantity' | 'maxUses' | 'maxUsesPerEmail' | 'minNights' | 'minAmount', server: string) => ({
    value: values[field],
    error: message(field, server),
    onChange: (event: { target: { value: string } }) => update(field, event.target.value),
  })
  const toggleApartment = (id: number) =>
    update('apartmentIds', values.apartmentIds.includes(id) ? values.apartmentIds.filter((value) => value !== id) : [...values.apartmentIds, id])

  // Résumé du code tel qu'il serait enregistré (rien tant que la saisie est incomplète).
  const preview = couponInputFromForm(values).input
  const extraName = extras.find((extra) => String(extra.id) === values.extraId)?.name ?? null
  const apartmentNames = apartments.filter((apartment) => values.apartmentIds.includes(apartment.id)).map((apartment) => apartment.name)
  const freeExtra = values.type === 'free_extra'

  const submit = () => {
    const result = couponInputFromForm(values)
    if (result.errors) setErrors(result.errors)
    else onSubmit(result.input)
  }

  return (
    <Dialog
      open
      size="lg"
      title={title}
      onClose={onClose}
      footer={
        <>
          <Button variant="secondary" onClick={onClose}>
            {t('admin.common.cancel')}
          </Button>
          <Button type="submit" form={formId} loading={pending}>
            {t('admin.common.save')}
          </Button>
        </>
      }
    >
      <form
        id={formId}
        noValidate
        className="flex flex-col gap-4"
        onSubmit={(event) => {
          event.preventDefault()
          submit()
        }}
      >
        {failed && <p className="text-sm text-danger">{t('admin.coupons.saveFailed')}</p>}
        <div className="flex items-end gap-2">
          <TextField label={t('admin.coupons.form.code')} className="flex-1" autoCapitalize="characters" maxLength={40} {...text('code', 'code')} />
          <Button variant="secondary" onClick={() => update('code', randomCode())}>
            {t('admin.coupons.form.generate')}
          </Button>
        </div>
        <div className="flex flex-col gap-2">
          <span className="text-sm font-semibold">{t('admin.coupons.form.type')}</span>
          <SegmentedControl
            label={t('admin.coupons.form.type')}
            value={values.type}
            onChange={(type) => update('type', type)}
            options={TYPES.map((type) => ({ value: type, label: t(`admin.coupons.form.types.${type}`) }))}
          />
        </div>
        {!freeExtra && (
          <div className="flex flex-col gap-2">
            <span className="text-sm font-semibold">{t('admin.coupons.form.target')}</span>
            <SegmentedControl
              label={t('admin.coupons.form.target')}
              value={values.target}
              onChange={(target) => update('target', target)}
              options={TARGETS.map((target) => ({ value: target, label: t(`admin.coupons.form.targets.${target}`) }))}
            />
          </div>
        )}
        <div className="grid gap-4 sm:grid-cols-2">
          {!freeExtra && (
            <TextField
              label={t(values.type === 'percent' ? 'admin.coupons.form.valuePercent' : 'admin.coupons.form.valueFixed')}
              suffix={values.type === 'percent' ? '%' : '€'}
              inputMode="decimal"
              {...text('value', 'value')}
            />
          )}
          {(freeExtra || values.target !== 'stay') && (
            <SelectField
              label={t('admin.coupons.form.extra')}
              value={values.extraId}
              error={message('extraId', 'extra_id')}
              onChange={(value) => update('extraId', value)}
              options={[{ value: '', label: t('admin.coupons.form.chooseExtra') }, ...extras.map((extra) => ({ value: String(extra.id), label: extra.name }))]}
            />
          )}
          {freeExtra && <TextField label={t('admin.coupons.form.freeQuantity')} inputMode="numeric" {...text('freeQuantity', 'free_quantity')} />}
        </div>
        <fieldset className="flex flex-col gap-4 border-t border-border pt-4">
          <legend className="sr-only">{t('admin.coupons.form.conditions')}</legend>
          <div aria-hidden="true">
            <p className="text-sm font-semibold">{t('admin.coupons.form.conditions')}</p>
            <p className="text-sm text-muted">{t('admin.coupons.form.conditionsHint')}</p>
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <TextField label={t('admin.coupons.form.minNights')} inputMode="numeric" {...text('minNights', 'min_nights')} />
            <TextField label={t('admin.coupons.form.minAmount')} suffix="€" inputMode="decimal" {...text('minAmount', 'min_amount')} />
            <DateField label={t('admin.coupons.form.startsOn')} value={values.startsOn} onChange={(value) => update('startsOn', value)} error={serverErrors.starts_on?.[0]} />
            <DateField label={t('admin.coupons.form.endsOn')} value={values.endsOn} min={values.startsOn || undefined} onChange={(value) => update('endsOn', value)} error={serverErrors.ends_on?.[0]} />
            <TextField label={t('admin.coupons.form.maxUses')} inputMode="numeric" {...text('maxUses', 'max_uses')} />
            {!values.automatic && <TextField label={t('admin.coupons.form.maxUsesPerEmail')} inputMode="numeric" {...text('maxUsesPerEmail', 'max_uses_per_email')} />}
          </div>
          <div className="flex flex-col gap-1">
            <span className="text-sm font-semibold">{t('admin.coupons.form.apartments')}</span>
            <span className="text-sm text-muted">{t('admin.coupons.form.apartmentsHint')}</span>
            <div className="grid gap-x-4 sm:grid-cols-2">
              {apartments.map((apartment) => (
                <Checkbox key={apartment.id} label={apartment.name} checked={values.apartmentIds.includes(apartment.id)} onChange={() => toggleApartment(apartment.id)} />
              ))}
            </div>
          </div>
        </fieldset>
        <Checkbox label={t('admin.coupons.form.automatic')} checked={values.automatic} onChange={(event) => update('automatic', event.target.checked)} />
        <Checkbox label={t('admin.coupons.form.active')} checked={values.active} onChange={(event) => update('active', event.target.checked)} />
        {preview && (
          <p className="rounded-xl bg-cream px-4 py-3 text-sm leading-relaxed" aria-live="polite">
            <strong>{t('admin.coupons.summary.title')} : </strong>
            {couponSummary(preview, extraName, apartmentNames, t)}
          </p>
        )}
      </form>
    </Dialog>
  )
}
