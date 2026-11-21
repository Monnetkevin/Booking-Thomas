import { centsToInput, parseEuros } from '@/lib/money'
import { POLICY_KEYS, type CancellationTier, type PolicyKey, type Settings } from './types'

/** Valeurs du formulaire, telles que saisies (montants en euros, texte). */
export interface SettingsFormValues {
  minNoticeDays: string
  horizonMonths: string
  taxMode: 'per_adult_night' | 'percentage'
  taxAmount: string
  taxPercentage: string
  taxCap: string
  policies: Record<PolicyKey, Array<{ daysBefore: string; refundPercent: string }>>
  emails: string[]
}

/** Erreurs détectées dans le navigateur : clés de traduction (admin.settings.errors.*). */
export type SettingsFormErrors = Partial<Record<string, 'invalidNumber' | 'invalidAmount' | 'invalidPercent' | 'duplicateDay' | 'noEmail'>>

export function formFromSettings(settings: Settings): SettingsFormValues {
  const tax = settings.tourist_tax
  return {
    minNoticeDays: String(settings.min_notice_days),
    horizonMonths: String(settings.horizon_months),
    taxMode: tax.mode,
    taxAmount: centsToInput(tax.amount),
    taxPercentage: tax.percentage === null ? '' : String(tax.percentage).replace('.', ','),
    taxCap: centsToInput(tax.cap_per_adult_night),
    policies: Object.fromEntries(
      POLICY_KEYS.map((key) => [
        key,
        settings.cancellation_policies[key].map((tier) => ({ daysBefore: String(tier.days_before), refundPercent: String(tier.refund_percent) })),
      ]),
    ) as SettingsFormValues['policies'],
    emails: settings.notification_emails.length > 0 ? settings.notification_emails : [''],
  }
}

function integer(value: string): number | null {
  return /^\d+$/.test(value.trim()) ? Number(value.trim()) : null
}

/** Valeurs envoyées à l'API (centimes, nombres), ou les erreurs à afficher. */
export function settingsFromForm(values: SettingsFormValues): { settings: Settings; errors?: undefined } | { settings?: undefined; errors: SettingsFormErrors } {
  const errors: SettingsFormErrors = {}
  const minNotice = integer(values.minNoticeDays)
  const horizon = integer(values.horizonMonths)
  if (minNotice === null) errors.minNoticeDays = 'invalidNumber'
  if (horizon === null) errors.horizonMonths = 'invalidNumber'

  const byPercentage = values.taxMode === 'percentage'
  const amount = byPercentage ? 0 : parseEuros(values.taxAmount || '0')
  const percentage = byPercentage ? Number(values.taxPercentage.replace(',', '.')) : null
  const cap = byPercentage && values.taxCap.trim() !== '' ? parseEuros(values.taxCap) : null
  if (amount === null) errors.taxAmount = 'invalidAmount'
  if (byPercentage && (values.taxPercentage.trim() === '' || percentage === null || Number.isNaN(percentage))) errors.taxPercentage = 'invalidPercent'
  if (byPercentage && values.taxCap.trim() !== '' && cap === null) errors.taxCap = 'invalidAmount'

  const policies = {} as Record<PolicyKey, CancellationTier[]>
  for (const key of POLICY_KEYS) {
    const tiers: CancellationTier[] = []
    values.policies[key].forEach((tier, index) => {
      const days = integer(tier.daysBefore)
      const percent = integer(tier.refundPercent)
      if (days === null) errors[`${key}.${index}.daysBefore`] = 'invalidNumber'
      if (percent === null || percent > 100) errors[`${key}.${index}.refundPercent`] = 'invalidPercent'
      if (days !== null && tiers.some((other) => other.days_before === days)) errors[`${key}.${index}.daysBefore`] = 'duplicateDay'
      tiers.push({ days_before: days ?? 0, refund_percent: percent ?? 0 })
    })
    policies[key] = [...tiers].sort((a, b) => b.days_before - a.days_before)
  }

  const emails = values.emails.map((email) => email.trim()).filter((email) => email !== '')
  if (emails.length === 0) errors.emails = 'noEmail'

  if (Object.keys(errors).length > 0) return { errors }

  return {
    settings: {
      min_notice_days: minNotice as number,
      horizon_months: horizon as number,
      tourist_tax: { mode: values.taxMode, amount: amount as number, percentage, cap_per_adult_night: cap },
      cancellation_policies: policies,
      notification_emails: emails,
    },
  }
}

/** Onglets de la page Réglages qui portent des champs du formulaire. */
export type SettingsTab = 'reservation' | 'paiement' | 'notifications'

/** Onglet d'une erreur (clé de champ ou de serveur), pour y ramener l'hôte. */
export function settingsTabOf(key: string): SettingsTab {
  if (key.startsWith('emails') || key.startsWith('notification_emails')) return 'notifications'
  if (/^(minNotice|horizon|min_notice|horizon)/i.test(key)) return 'reservation'
  return 'paiement'
}
