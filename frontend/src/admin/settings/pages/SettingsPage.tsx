import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { PageHeader } from '@/admin/components/PageHeader'
import { ErrorState } from '@/components/feedback/ErrorState'
import { TrashIcon } from '@/components/icons'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { SelectField, TextField } from '@/components/ui/Field'
import { Section } from '@/components/ui/Section'
import { Spinner } from '@/components/ui/Spinner'
import { ApiError } from '@/lib/api'
import { PolicyEditor } from '../components/PolicyEditor'
import { formFromSettings, settingsFromForm, type SettingsFormErrors, type SettingsFormValues } from '../settingsForm'
import { POLICY_KEYS, type Settings } from '../types'
import { useSettings, useUpdateSettings } from '../useSettings'

const MAX_EMAILS = 5

/** Réglages de l'hôte : délais de réservation, taxe de séjour, annulation, e-mails. */
export function SettingsPage() {
  const { t } = useTranslation()
  const settings = useSettings()
  // La mutation vit ici et non dans le formulaire : celui-ci est recréé (clé) quand les
  // réglages enregistrés changent, ce qui effacerait sinon le message « Enregistré ».
  const update = useUpdateSettings()

  return (
    <>
      <PageHeader title={t('admin.settings.title')} subtitle={t('admin.settings.subtitle')} />
      {settings.isPending ? (
        <Spinner label={t('common.loading')} />
      ) : settings.isError ? (
        <ErrorState onRetry={() => void settings.refetch()} />
      ) : (
        <SettingsForm key={JSON.stringify(settings.data)} settings={settings.data} update={update} />
      )}
    </>
  )
}

function SettingsForm({ settings, update }: { settings: Settings; update: ReturnType<typeof useUpdateSettings> }) {
  const { t } = useTranslation()
  const [values, setValues] = useState<SettingsFormValues>(() => formFromSettings(settings))
  const [errors, setErrors] = useState<SettingsFormErrors>({})
  const set = <K extends keyof SettingsFormValues>(key: K, value: SettingsFormValues[K]) => setValues((current) => ({ ...current, [key]: value }))
  const error = (key: string) => (errors[key] ? t(`admin.settings.errors.${errors[key]}`) : undefined)
  const serverErrors = update.error instanceof ApiError ? update.error.fieldErrors : {}

  const submit = (event: FormEvent) => {
    event.preventDefault()
    const result = settingsFromForm(values)
    setErrors(result.errors ?? {})
    if (result.settings) update.mutate(result.settings)
  }

  return (
    <form onSubmit={submit} className="space-y-5" noValidate>
      <Section title={t('admin.settings.bookingTitle')} description={t('admin.settings.bookingHint')}>
        <div className="grid gap-4 sm:grid-cols-2">
          <TextField
            label={t('admin.settings.minNotice')}
            inputMode="numeric"
            suffix={t('admin.settings.daysSuffix')}
            value={values.minNoticeDays}
            error={error('minNoticeDays') ?? serverErrors.min_notice_days?.[0]}
            onChange={(event) => set('minNoticeDays', event.target.value)}
          />
          <TextField
            label={t('admin.settings.horizon')}
            inputMode="numeric"
            suffix={t('admin.settings.monthsSuffix')}
            value={values.horizonMonths}
            error={error('horizonMonths') ?? serverErrors.horizon_months?.[0]}
            onChange={(event) => set('horizonMonths', event.target.value)}
          />
        </div>
      </Section>

      <Section title={t('admin.settings.taxTitle')} description={t('admin.settings.taxHint')}>
        <div className="grid gap-4 sm:grid-cols-2">
          <SelectField
            label={t('admin.settings.taxMode')}
            value={values.taxMode}
            onChange={(value) => set('taxMode', value as SettingsFormValues['taxMode'])}
            options={[
              { value: 'per_adult_night', label: t('admin.settings.taxModes.per_adult_night') },
              { value: 'percentage', label: t('admin.settings.taxModes.percentage') },
            ]}
          />
          {values.taxMode === 'per_adult_night' ? (
            <TextField
              label={t('admin.settings.taxAmount')}
              inputMode="decimal"
              suffix="€"
              value={values.taxAmount}
              error={error('taxAmount') ?? serverErrors['tourist_tax.amount']?.[0]}
              onChange={(event) => set('taxAmount', event.target.value)}
            />
          ) : (
            <>
              <TextField
                label={t('admin.settings.taxPercentage')}
                inputMode="decimal"
                suffix="%"
                value={values.taxPercentage}
                error={error('taxPercentage') ?? serverErrors['tourist_tax.percentage']?.[0]}
                onChange={(event) => set('taxPercentage', event.target.value)}
              />
              <TextField
                label={t('admin.settings.taxCap')}
                hint={t('admin.settings.taxCapHint')}
                inputMode="decimal"
                suffix="€"
                value={values.taxCap}
                error={error('taxCap') ?? serverErrors['tourist_tax.cap_per_adult_night']?.[0]}
                onChange={(event) => set('taxCap', event.target.value)}
              />
            </>
          )}
        </div>
      </Section>

      <Section title={t('admin.settings.cancellationTitle')} description={t('admin.settings.cancellationHint')}>
        <div className="space-y-6">
          {POLICY_KEYS.map((policy) => (
            <PolicyEditor
              key={policy}
              policy={policy}
              tiers={values.policies[policy]}
              errors={errors}
              onChange={(tiers) => set('policies', { ...values.policies, [policy]: tiers })}
            />
          ))}
        </div>
      </Section>

      <Section title={t('admin.settings.emailsTitle')} description={t('admin.settings.emailsHint')}>
        <div className="space-y-3">
          {values.emails.map((email, index) => (
            <div key={index} className="flex items-end gap-2">
              <TextField
                className="flex-1"
                label={t('admin.settings.email', { number: index + 1 })}
                type="email"
                value={email}
                error={serverErrors[`notification_emails.${index}`]?.[0]}
                onChange={(event) => set('emails', values.emails.map((value, i) => (i === index ? event.target.value : value)))}
              />
              {values.emails.length > 1 && (
                <Button
                  variant="ghost"
                  aria-label={t('admin.settings.removeEmail')}
                  title={t('admin.settings.removeEmail')}
                  onClick={() => set('emails', values.emails.filter((_, i) => i !== index))}
                >
                  <TrashIcon />
                </Button>
              )}
            </div>
          ))}
          {error('emails') && <p className="text-sm text-danger">{error('emails')}</p>}
          {values.emails.length < MAX_EMAILS && (
            <Button variant="secondary" onClick={() => set('emails', [...values.emails, ''])}>
              {t('admin.settings.addEmail')}
            </Button>
          )}
        </div>
      </Section>

      {update.isSuccess && <Alert tone="success">{t('admin.common.saved')}</Alert>}
      {(Object.keys(errors).length > 0 || (update.error instanceof ApiError && update.error.status === 422)) && (
        <Alert tone="danger">{t('admin.settings.errors.fix')}</Alert>
      )}
      {update.isError && !(update.error instanceof ApiError && update.error.status === 422) && <Alert tone="danger">{t('common.error')}</Alert>}
      <Button type="submit" loading={update.isPending}>
        {update.isPending ? t('admin.common.saving') : t('admin.common.save')}
      </Button>
    </form>
  )
}
