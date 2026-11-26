import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/components/ui/Button'
import { Dialog } from '@/components/ui/Dialog'
import { Checkbox, SelectField, TextArea, TextField } from '@/components/ui/Field'
import { extraInputFromForm, type ExtraFormErrors, type ExtraFormValues } from '../extraForm'
import { EXTRA_PRICINGS, type ExtraInput, type ExtraPricing } from '../types'

interface ExtraFormDialogProps {
  title: string
  initialValues: ExtraFormValues
  apartments: Array<{ id: number; name: string }>
  pending: boolean
  failed: boolean
  onSubmit: (input: ExtraInput) => void
  onClose: () => void
}

/** Création ou modification d'une option : textes, prix, mode, quantité, délai, logements. */
export function ExtraFormDialog({ title, initialValues, apartments, pending, failed, onSubmit, onClose }: ExtraFormDialogProps) {
  const { t } = useTranslation()
  const [values, setValues] = useState(initialValues)
  const [errors, setErrors] = useState<ExtraFormErrors>({})
  const formId = 'option-formulaire'

  const update = <K extends keyof ExtraFormValues>(field: K, value: ExtraFormValues[K]) => {
    setValues((current) => ({ ...current, [field]: value }))
    setErrors(({ [field]: _removed, ...rest }) => rest)
  }
  const message = (field: keyof ExtraFormValues) => (errors[field] ? t(`admin.extras.errors.${errors[field]}`) : undefined)
  const text = (field: 'nameFr' | 'nameEn' | 'price' | 'maxQuantity' | 'leadTimeHours') => ({
    value: values[field],
    error: message(field),
    onChange: (event: { target: { value: string } }) => update(field, event.target.value),
  })
  const toggleApartment = (id: number) =>
    update('apartmentIds', values.apartmentIds.includes(id) ? values.apartmentIds.filter((value) => value !== id) : [...values.apartmentIds, id])

  const submit = () => {
    const result = extraInputFromForm(values)
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
        {failed && <p className="text-sm text-danger">{t('admin.extras.saveFailed')}</p>}
        <div className="grid gap-4 sm:grid-cols-2">
          <TextField label={t('admin.extras.form.nameFr')} required maxLength={120} {...text('nameFr')} />
          <TextField label={t('admin.extras.form.nameEn')} hint={t('admin.extras.form.optional')} maxLength={120} {...text('nameEn')} />
          <TextArea label={t('admin.extras.form.descriptionFr')} rows={2} maxLength={500} value={values.descriptionFr} onChange={(event) => update('descriptionFr', event.target.value)} />
          <TextArea label={t('admin.extras.form.descriptionEn')} rows={2} maxLength={500} value={values.descriptionEn} onChange={(event) => update('descriptionEn', event.target.value)} />
        </div>
        <div className="grid items-end gap-4 sm:grid-cols-2">
          <TextField label={t('admin.extras.form.price')} suffix="€" inputMode="decimal" disabled={values.free} {...text('price')} />
          <Checkbox label={t('admin.extras.form.free')} checked={values.free} onChange={(event) => update('free', event.target.checked)} />
        </div>
        <div className="grid gap-4 sm:grid-cols-3">
          <SelectField
            label={t('admin.extras.form.pricing')}
            value={values.pricing}
            onChange={(value) => update('pricing', value as ExtraPricing)}
            options={EXTRA_PRICINGS.map((pricing) => ({ value: pricing, label: t(`admin.extras.pricing.${pricing}`) }))}
          />
          {values.pricing === 'per_unit' && (
            <TextField label={t('admin.extras.form.maxQuantity')} inputMode="numeric" {...text('maxQuantity')} />
          )}
          <TextField label={t('admin.extras.form.leadTime')} hint={t('admin.extras.form.leadTimeHint')} suffix="h" inputMode="numeric" {...text('leadTimeHours')} />
        </div>
        <fieldset className="flex flex-col gap-1">
          <legend className="mb-1 text-sm font-semibold">{t('admin.extras.form.apartments')}</legend>
          <div className="grid gap-x-4 sm:grid-cols-2">
            {apartments.map((apartment) => (
              <Checkbox key={apartment.id} label={apartment.name} checked={values.apartmentIds.includes(apartment.id)} onChange={() => toggleApartment(apartment.id)} />
            ))}
          </div>
        </fieldset>
        <Checkbox label={t('admin.extras.form.active')} checked={values.active} onChange={(event) => update('active', event.target.checked)} />
      </form>
    </Dialog>
  )
}
