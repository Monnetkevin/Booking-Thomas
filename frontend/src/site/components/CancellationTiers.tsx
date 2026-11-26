import { useTranslation } from 'react-i18next'

export interface CancellationTerms {
  policy: 'flexible' | 'moderate' | 'strict' | string
  tiers: Array<{ days_before: number; refund_percent: number }>
}

/** Barème d'annulation : part remboursée selon le délai avant l'arrivée. */
export function CancellationTiers({ terms }: { terms: CancellationTerms }) {
  const { t } = useTranslation()
  if (terms.tiers.length === 0) return null

  return (
    <div>
      <h3 className="text-base">{t('detail.cancellation', { policy: t(`detail.policies.${terms.policy}`, { defaultValue: terms.policy }) })}</h3>
      <ul className="mt-1 list-disc pl-5 text-[15px] text-muted">
        {terms.tiers.map((tier) =>
          tier.days_before > 0 ? (
            <li key={tier.days_before}>{t('detail.cancellationTier', { percent: tier.refund_percent, days: tier.days_before })}</li>
          ) : (
            <li key="last">{t('detail.cancellationTierLast', { percent: tier.refund_percent })}</li>
          ),
        )}
      </ul>
    </div>
  )
}
