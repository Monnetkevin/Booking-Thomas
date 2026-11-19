import { describe, expect, it } from 'vitest'
import { SUMMARY_HOURS, toFormState, toInput } from './preferencesForm'
import type { NotificationPreferences } from './types'

const host: NotificationPreferences = {
  types: [
    { type: 'ical_conflict', email: true, editable: false },
    { type: 'ical_feed_failing', email: true, editable: true },
    { type: 'booking_confirmed', email: false, editable: false },
  ],
  summary_enabled: true,
  summary_hour: 18,
}

describe('formulaire des préférences', () => {
  it('n’envoie que les types réglables, sans récapitulatif pour l’hôte', () => {
    const state = toFormState(host)
    state.email.ical_feed_failing = false
    state.email.ical_conflict = false

    expect(toInput(state, host)).toEqual({ email: { ical_feed_failing: false } })
  })

  it('envoie le récapitulatif du ménage', () => {
    const cleaner: NotificationPreferences = { ...host, types: [{ type: 'daily_summary', email: true, editable: true }] }

    expect(toInput({ ...toFormState(cleaner), summaryHour: 7 }, cleaner)).toEqual({
      email: { daily_summary: true },
      summary_enabled: true,
      summary_hour: 7,
    })
  })

  it('propose les heures de 6 h à 22 h', () => {
    expect(SUMMARY_HOURS[0]).toBe(6)
    expect(SUMMARY_HOURS.at(-1)).toBe(22)
  })
})
