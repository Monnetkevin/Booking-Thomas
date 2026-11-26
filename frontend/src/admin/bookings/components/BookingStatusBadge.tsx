import { useTranslation } from 'react-i18next'
import { Badge, type BadgeTone } from '@/components/ui/Badge'
import type { BookingStatus } from '../types'

const tones: Record<BookingStatus, BadgeTone> = {
  pending: 'warning',
  confirmed: 'success',
  completed: 'neutral',
  cancelled: 'danger',
  expired: 'neutral',
}

export function BookingStatusBadge({ status }: { status: BookingStatus }) {
  const { t } = useTranslation()
  return <Badge tone={tones[status]}>{t(`admin.bookings.statuses.${status}`)}</Badge>
}
