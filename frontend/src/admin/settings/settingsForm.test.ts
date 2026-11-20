import { describe, expect, it } from 'vitest'
import { formFromSettings, settingsFromForm } from './settingsForm'
import type { Settings } from './types'

const settings: Settings = {
  min_notice_days: 1,
  horizon_months: 12,
  tourist_tax: { mode: 'per_adult_night', amount: 130, percentage: null, cap_per_adult_night: null },
  cancellation_policies: {
    flexible: [{ days_before: 1, refund_percent: 100 }, { days_before: 0, refund_percent: 0 }],
    moderate: [{ days_before: 5, refund_percent: 100 }, { days_before: 0, refund_percent: 50 }],
    strict: [{ days_before: 14, refund_percent: 100 }, { days_before: 0, refund_percent: 0 }],
  },
  notification_emails: ['hote@example.com'],
}

describe('formulaire des réglages', () => {
  it('revient aux mêmes réglages après un aller-retour, montants en euros', () => {
    const form = formFromSettings(settings)
    expect(form.taxAmount).toBe('1,30')
    expect(settingsFromForm(form).settings).toEqual(settings)
  })

  it('trie les paliers et passe en pourcentage avec plafond', () => {
    const form = formFromSettings(settings)
    form.policies.moderate = [{ daysBefore: '0', refundPercent: '50' }, { daysBefore: '7', refundPercent: '100' }]
    form.taxMode = 'percentage'
    form.taxPercentage = '5,5'
    form.taxCap = '4'

    const result = settingsFromForm(form).settings
    expect(result?.cancellation_policies.moderate[0]).toEqual({ days_before: 7, refund_percent: 100 })
    expect(result?.tourist_tax).toEqual({ mode: 'percentage', amount: 0, percentage: 5.5, cap_per_adult_night: 400 })
  })

  it('signale les saisies invalides', () => {
    const form = formFromSettings(settings)
    form.minNoticeDays = 'deux'
    form.policies.strict = [{ daysBefore: '3', refundPercent: '120' }, { daysBefore: '3', refundPercent: '0' }]
    form.emails = ['  ']

    expect(settingsFromForm(form).errors).toEqual({
      minNoticeDays: 'invalidNumber',
      'strict.0.refundPercent': 'invalidPercent',
      'strict.1.daysBefore': 'duplicateDay',
      emails: 'noEmail',
    })
  })
})
