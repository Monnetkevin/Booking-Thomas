import type { NotificationPreferences, NotificationPreferencesInput, NotificationType } from './types'

export interface PreferencesFormState {
  email: Partial<Record<NotificationType, boolean>>
  summaryEnabled: boolean
  summaryHour: number
}

/** Heures proposées pour le récapitulatif du ménage (spec : de 6 h à 22 h). */
export const SUMMARY_HOURS = Array.from({ length: 17 }, (_, index) => index + 6)

export function toFormState(preferences: NotificationPreferences): PreferencesFormState {
  return {
    email: Object.fromEntries(preferences.types.map((type) => [type.type, type.email])),
    summaryEnabled: preferences.summary_enabled,
    summaryHour: preferences.summary_hour,
  }
}

/** Corps envoyé à l'API : seuls les types réglables, et le récapitulatif s'il concerne le compte. */
export function toInput(state: PreferencesFormState, preferences: NotificationPreferences): NotificationPreferencesInput {
  const editable = preferences.types.filter((type) => type.editable).map((type) => type.type)
  const hasSummary = preferences.types.some((type) => type.type === 'daily_summary')

  return {
    email: Object.fromEntries(editable.map((type) => [type, state.email[type] ?? false])),
    ...(hasSummary ? { summary_enabled: state.summaryEnabled, summary_hour: state.summaryHour } : {}),
  }
}
