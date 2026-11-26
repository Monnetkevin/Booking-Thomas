import { useTranslation } from 'react-i18next'
import { Badge } from '@/components/ui/Badge'
import type { ApartmentStatus } from '../types'

export function StatusBadge({ status }: { status: ApartmentStatus }) {
  const { t } = useTranslation()

  return (
    <Badge tone={status === 'published' ? 'success' : 'neutral'}>{t(`admin.apartments.status.${status}`)}</Badge>
  )
}
