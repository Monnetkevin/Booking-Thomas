import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Button } from '@/components/ui/Button'
import { TextField } from '@/components/ui/Field'
import { cn } from '@/lib/cn'
import { ALL_DAYS, rateInputFromForm, type RateFormErrors, type RateFormValues } from '../rateForm'
import type { IsoDay, RatePeriodInput } from '../types'

interface RatePeriodFormProps {
  initialValues: RateFormValues
  /** Séjour minimum du logement, utilisé quand la période n'en fixe pas. */
  apartmentMinStay: number
  pending: boolean
  serverErrors?: RateFormErrors
  onSubmit: (input: RatePeriodInput) => void
  onCancel: () => void
}

/** Saisie d'une période tarifaire ; l'enregistrement est confié au parent. */
export function RatePeriodForm({ initialValues, apartmentMinStay, pending, serverErrors = {}, onSubmit, onCancel }: RatePeriodFormProps) {
  const { t } = useTranslation()
  const [values, setValues] = useState(initialValues)
  const [localErrors, setLocalErrors] = useState<RateFormErrors>({})

  const errors: RateFormErrors = { ...serverErrors, ...localErrors }
  const message = (field: keyof RateFormValues) => {
    const error = errors[field]
    if (error === 'invalidAmount') return t('admin.apartments.invalidAmount')
    if (error === 'noDay') return t('admin.rates.form.noDay')
    return error
  }

  const update = <K extends keyof RateFormValues>(field: K, value: RateFormValues[K]) => {
    setValues((current) => ({ ...current, [field]: value }))
    setLocalErrors(({ [field]: _removed, ...rest }) => rest)
  }

  const bind = (field: 'label' | 'startsOn' | 'endsOn' | 'nightlyPrice' | 'minStay' | 'weekendSupplement') => ({
    name: field,
    value: values[field],
    error: message(field),
    onChange: (event: { target: { value: string } }) => update(field, event.target.value),
  })

  const toggleDay = (day: IsoDay) =>
    update('arrivalDays', values.arrivalDays.includes(day) ? values.arrivalDays.filter((d) => d !== day) : [...values.arrivalDays, day])

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    const result = rateInputFromForm(values)
    if (result.errors) setLocalErrors(result.errors)
    else onSubmit(result.input)
  }

  const daysError = message('arrivalDays')

  return (
    <form onSubmit={handleSubmit} noValidate className="flex flex-col gap-4">
      {/* Focus à l'ouverture : sur téléphone, le formulaire vient aussi à l'écran. */}
      <TextField label={t('admin.rates.form.label')} hint={t('admin.rates.form.labelHint')} maxLength={80} autoFocus {...bind('label')} />
      <div className="grid grid-cols-2 gap-3">
        <TextField label={t('admin.rates.form.startsOn')} type="date" required {...bind('startsOn')} />
        <TextField label={t('admin.rates.form.endsOn')} type="date" required min={values.startsOn || undefined} {...bind('endsOn')} />
      </div>
      <div className="grid grid-cols-2 gap-3">
        <TextField label={t('admin.rates.form.nightlyPrice')} inputMode="decimal" required suffix="€" {...bind('nightlyPrice')} />
        <TextField
          label={t('admin.rates.form.minStay')}
          hint={t('admin.rates.form.minStayHint', { count: apartmentMinStay })}
          type="number"
          inputMode="numeric"
          min={1}
          placeholder={String(apartmentMinStay)}
          suffix={t('admin.apartments.units.nights')}
          {...bind('minStay')}
        />
      </div>
      <TextField
        label={t('admin.rates.form.weekend')}
        hint={t('admin.rates.form.weekendHint')}
        inputMode="decimal"
        suffix="€"
        {...bind('weekendSupplement')}
      />
      <fieldset aria-describedby="arrival-days-hint">
        <legend className="mb-1.5 text-sm font-semibold">{t('admin.rates.form.arrivalDays')}</legend>
        <div className="flex flex-wrap gap-1.5">
          {ALL_DAYS.map((day) => {
            const checked = values.arrivalDays.includes(day)
            return (
              <label
                key={day}
                className={cn(
                  'flex min-h-11 min-w-12 cursor-pointer items-center justify-center rounded-[10px] border px-2 text-sm font-semibold has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-accent',
                  checked ? 'border-accent bg-accent-soft text-accent-strong' : 'border-border bg-white text-muted',
                )}
              >
                <input type="checkbox" className="sr-only" checked={checked} onChange={() => toggleDay(day)} />
                {t(`admin.rates.days.${day}`)}
              </label>
            )
          })}
        </div>
        <p id="arrival-days-hint" className="mt-1.5 text-sm text-muted">
          {t('admin.rates.form.arrivalDaysHint')}
        </p>
        {daysError && <p className="mt-1 text-sm font-medium text-danger">{daysError}</p>}
      </fieldset>
      <div className="flex flex-wrap justify-end gap-2.5">
        <Button variant="ghost" onClick={onCancel}>
          {t('admin.common.cancel')}
        </Button>
        <Button type="submit" loading={pending}>
          {t('admin.rates.form.save')}
        </Button>
      </div>
    </form>
  )
}
