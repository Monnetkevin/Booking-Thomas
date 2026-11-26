export type PolicyKey = 'flexible' | 'moderate' | 'strict'

export const POLICY_KEYS: PolicyKey[] = ['flexible', 'moderate', 'strict']

export interface CancellationTier {
  days_before: number
  refund_percent: number
}

export interface TouristTax {
  mode: 'per_adult_night' | 'percentage'
  /** Centimes par adulte et par nuit (mode per_adult_night). */
  amount: number
  /** Pourcentage du prix de la nuit par voyageur (mode percentage). */
  percentage: number | null
  cap_per_adult_night: number | null
}

export interface Settings {
  min_notice_days: number
  horizon_months: number
  tourist_tax: TouristTax
  cancellation_policies: Record<PolicyKey, CancellationTier[]>
  notification_emails: string[]
}
