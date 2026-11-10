import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/components/ui/Button'
import { SelectField, TextArea, TextField } from '@/components/ui/Field'
import { Section } from '@/components/ui/Section'
import {
  apartmentInputFromForm,
  type ApartmentFormErrors,
  type ApartmentFormValues,
} from '../apartmentForm'
import type { ApartmentInput, CancellationPolicy } from '../types'

interface ApartmentFormProps {
  initialValues: ApartmentFormValues
  submitLabel: string
  pending: boolean
  /** Erreurs renvoyées par l'API, déjà rattachées aux champs. */
  serverErrors?: ApartmentFormErrors
  onSubmit: (input: ApartmentInput) => void
}

const policies: CancellationPolicy[] = ['flexible', 'moderate', 'strict']

/**
 * Informations, adresse et règles du séjour d'un logement. Ne fait que saisir et
 * convertir ; l'enregistrement est confié au parent.
 */
export function ApartmentForm({ initialValues, submitLabel, pending, serverErrors = {}, onSubmit }: ApartmentFormProps) {
  const { t } = useTranslation()
  const [values, setValues] = useState(initialValues)
  const [localErrors, setLocalErrors] = useState<ApartmentFormErrors>({})

  const errors = { ...serverErrors, ...localErrors }
  const error = (field: keyof ApartmentFormValues) =>
    errors[field] === 'invalidAmount' ? t('admin.apartments.invalidAmount') : errors[field]

  /** Props communes d'un champ lié à une clé du formulaire. */
  const bind = (field: keyof ApartmentFormValues) => ({
    name: field,
    value: values[field],
    error: error(field),
    onChange: (event: { target: { value: string } }) => {
      setValues((current) => ({ ...current, [field]: event.target.value }))
      setLocalErrors((current) => {
        const { [field]: _removed, ...rest } = current
        return rest
      })
    },
  })

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const result = apartmentInputFromForm(values)
    if (result.errors) {
      setLocalErrors(result.errors)
      return
    }
    onSubmit(result.input)
  }

  const f = (key: string) => t(`admin.apartments.fields.${key}` as 'admin.apartments.fields.nameFr')

  return (
    <form onSubmit={handleSubmit} noValidate className="flex flex-col gap-5">
      <Section title={t('admin.apartments.sections.info')}>
        <div className="grid gap-4 sm:grid-cols-2">
          <TextField label={f('nameFr')} required maxLength={120} {...bind('nameFr')} />
          <TextField label={f('nameEn')} hint={f('translationHint')} maxLength={120} {...bind('nameEn')} />
          <TextArea label={f('descriptionFr')} hint={f('descriptionHint')} maxLength={5000} {...bind('descriptionFr')} />
          <TextArea label={f('descriptionEn')} hint={f('translationHint')} maxLength={5000} {...bind('descriptionEn')} />
        </div>
        <div className="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
          <TextField label={f('surface')} type="number" inputMode="numeric" min={5} suffix={t('admin.apartments.units.m2')} {...bind('surface')} />
          <TextField label={f('capacity')} type="number" inputMode="numeric" min={1} required {...bind('capacity')} />
          <TextField label={f('bedrooms')} type="number" inputMode="numeric" min={0} required {...bind('bedrooms')} />
          <TextField label={f('beds')} type="number" inputMode="numeric" min={1} required {...bind('beds')} />
          <TextField label={f('bathrooms')} type="number" inputMode="numeric" min={0} required {...bind('bathrooms')} />
          <TextField label={f('checkIn')} type="time" required {...bind('checkInTime')} />
          <TextField label={f('checkOut')} type="time" required {...bind('checkOutTime')} />
          <TextField
            label={f('cleaningFee')}
            hint={f('cleaningFeeHint')}
            inputMode="decimal"
            suffix={t('admin.apartments.units.euro')}
            {...bind('cleaningFee')}
          />
        </div>
        <TextField
          className="mt-4"
          label={f('slug')}
          hint={t('admin.apartments.fields.slugHint', { slug: values.slug || '…' })}
          maxLength={80}
          {...bind('slug')}
        />
      </Section>

      <Section title={t('admin.apartments.sections.location')}>
        <div className="grid gap-4 sm:grid-cols-[2fr_1fr_1fr]">
          <TextField label={f('address')} hint={f('addressHint')} required autoComplete="street-address" {...bind('address')} />
          <TextField label={f('postalCode')} required inputMode="numeric" maxLength={5} autoComplete="postal-code" {...bind('postalCode')} />
          <TextField label={f('city')} required autoComplete="address-level2" {...bind('city')} />
        </div>
        <div className="mt-4 grid gap-4 sm:grid-cols-2">
          <TextField label={f('latitude')} hint={f('coordinatesHint')} inputMode="decimal" {...bind('latitude')} />
          <TextField label={f('longitude')} inputMode="decimal" {...bind('longitude')} />
        </div>
      </Section>

      <Section title={t('admin.apartments.sections.rules')}>
        <div className="grid gap-4 sm:grid-cols-3">
          <TextField
            label={f('minStay')}
            hint={f('minStayHint')}
            type="number"
            inputMode="numeric"
            min={1}
            suffix={t('admin.apartments.units.nights')}
            {...bind('minStay')}
          />
          <TextField label={f('maxStay')} type="number" inputMode="numeric" min={1} suffix={t('admin.apartments.units.nights')} {...bind('maxStay')} />
          <TextField
            label={f('turnover')}
            hint={f('turnoverHint')}
            type="number"
            inputMode="numeric"
            min={0}
            max={7}
            suffix={t('admin.apartments.units.nights')}
            {...bind('turnoverDays')}
          />
          <SelectField
            label={f('policy')}
            options={policies.map((policy) => ({ value: policy, label: t(`admin.apartments.policies.${policy}`) }))}
            {...bind('cancellationPolicy')}
          />
          <TextField label={f('registration')} hint={f('registrationHint')} maxLength={50} className="sm:col-span-2" {...bind('registrationNumber')} />
        </div>
      </Section>

      <div className="sticky bottom-0 -mx-4 flex justify-end border-t border-border bg-sand/95 px-4 py-3 backdrop-blur sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:p-0">
        <Button type="submit" size="lg" loading={pending}>
          {pending ? t('admin.common.saving') : submitLabel}
        </Button>
      </div>
    </form>
  )
}
