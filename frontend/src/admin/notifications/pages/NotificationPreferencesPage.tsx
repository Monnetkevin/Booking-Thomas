import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { PageHeader } from '@/admin/components/PageHeader'
import { ErrorState } from '@/components/feedback/ErrorState'
import { ArrowLeftIcon } from '@/components/icons'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Checkbox, SelectField } from '@/components/ui/Field'
import { Section } from '@/components/ui/Section'
import { Spinner } from '@/components/ui/Spinner'
import { SUMMARY_HOURS, toFormState, toInput, type PreferencesFormState } from '../preferencesForm'
import type { NotificationPreferences } from '../types'
import { useNotificationPreferences, useUpdateNotificationPreferences } from '../useNotifications'

/** Préférences du compte connecté : e-mail par type, récapitulatif du ménage. */
export function NotificationPreferencesPage() {
  const { t } = useTranslation()
  const preferences = useNotificationPreferences()

  return (
    <>
      <Link to="/admin/notifications" className="mb-3 inline-flex min-h-11 items-center gap-2 text-sm font-semibold no-underline">
        <ArrowLeftIcon width={18} height={18} />
        {t('admin.notifications.title')}
      </Link>
      <PageHeader title={t('admin.notifications.preferences.title')} subtitle={t('admin.notifications.preferences.subtitle')} />
      {preferences.isPending ? (
        <Spinner label={t('common.loading')} />
      ) : preferences.isError ? (
        <ErrorState onRetry={() => void preferences.refetch()} />
      ) : (
        <PreferencesForm key={JSON.stringify(preferences.data)} preferences={preferences.data} />
      )}
    </>
  )
}

function PreferencesForm({ preferences }: { preferences: NotificationPreferences }) {
  const { t } = useTranslation()
  const [state, setState] = useState<PreferencesFormState>(() => toFormState(preferences))
  const update = useUpdateNotificationPreferences()
  const hasSummary = preferences.types.some((type) => type.type === 'daily_summary')

  const submit = (event: FormEvent) => {
    event.preventDefault()
    update.mutate(toInput(state, preferences))
  }

  return (
    <form onSubmit={submit} className="space-y-5">
      <Section title={t('admin.notifications.preferences.emailTitle')} description={t('admin.notifications.preferences.emailHint')}>
        <ul className="space-y-1">
          {preferences.types.map((type) => (
            <li key={type.type}>
              <Checkbox
                label={t(`admin.notifications.types.${type.type}`)}
                checked={state.email[type.type] ?? false}
                disabled={!type.editable}
                onChange={(event) => setState((current) => ({ ...current, email: { ...current.email, [type.type]: event.target.checked } }))}
              />
              {!type.editable && (
                <p className="mb-2 ml-7.5 text-sm text-muted">
                  {type.email ? t('admin.notifications.preferences.forced') : t('admin.notifications.preferences.elsewhere')}
                </p>
              )}
            </li>
          ))}
        </ul>
      </Section>

      {hasSummary && (
        <Section title={t('admin.notifications.preferences.summaryTitle')} description={t('admin.notifications.preferences.summaryHint')}>
          <Checkbox
            label={t('admin.notifications.preferences.summaryEnabled')}
            checked={state.summaryEnabled}
            onChange={(event) => setState((current) => ({ ...current, summaryEnabled: event.target.checked }))}
          />
          <SelectField
            className="mt-3 max-w-xs"
            label={t('admin.notifications.preferences.summaryHour')}
            disabled={!state.summaryEnabled}
            value={String(state.summaryHour)}
            onChange={(value) => setState((current) => ({ ...current, summaryHour: Number(value) }))}
            options={SUMMARY_HOURS.map((hour) => ({ value: String(hour), label: t('admin.notifications.preferences.hour', { hour }) }))}
          />
        </Section>
      )}

      {update.isSuccess && <Alert tone="success">{t('admin.common.saved')}</Alert>}
      {update.isError && <Alert tone="danger">{t('common.error')}</Alert>}
      <Button type="submit" loading={update.isPending}>
        {update.isPending ? t('admin.common.saving') : t('admin.common.save')}
      </Button>
    </form>
  )
}
