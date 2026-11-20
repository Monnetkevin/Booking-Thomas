import { useTranslation } from 'react-i18next'
import { Badge, type BadgeTone } from '@/components/ui/Badge'
import type { PaymentState } from '../types'

const tones: Record<PaymentState, BadgeTone> = {
  awaiting: 'warning',
  unpaid: 'neutral',
  paid: 'success',
  partially_refunded: 'warning',
  refunded: 'neutral',
}

/** État du règlement : payé, remboursé, en attente… */
export function PaymentStateBadge({ state }: { state: PaymentState }) {
  const { t } = useTranslation()
  return <Badge tone={tones[state]}>{t(`admin.bookings.paymentStates.${state}`)}</Badge>
}
