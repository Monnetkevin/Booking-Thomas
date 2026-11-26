import { useTranslation } from 'react-i18next'
import { Badge, type BadgeTone } from '@/components/ui/Badge'
import { formatHour, isLeaving } from '../cleaningFormat'
import type { CleaningMovement, CleaningMovementKind } from '../types'

const tones: Record<CleaningMovementKind, BadgeTone> = {
  departure: 'warning',
  arrival: 'accent',
  blocked: 'neutral',
  occupied: 'neutral',
  occupied_end: 'warning',
}

/** Une ligne du planning du ménage : logement, nature, heure, voyageurs et notes. */
export function CleaningMovementRow({ movement }: { movement: CleaningMovement }) {
  const { t } = useTranslation()
  const time = movement.time ? formatHour(movement.time) : null

  return (
    <div className="flex flex-col gap-1.5 py-3 sm:flex-row sm:items-start sm:gap-4">
      <p className="font-semibold sm:w-40 sm:shrink-0">{movement.apartment}</p>
      <div className="flex flex-1 flex-col gap-1.5">
        <div className="flex flex-wrap items-center gap-2">
          <Badge tone={tones[movement.kind]}>{t(`admin.cleaning.kinds.${movement.kind}`)}</Badge>
          {time && <span className="text-sm">{t(`admin.cleaning.time.${isLeaving(movement.kind) ? 'before' : 'from'}`, { time })}</span>}
          {movement.guests !== null && <span className="text-sm text-muted">{t('admin.cleaning.guests', { count: movement.guests })}</span>}
          {/* Signalé une fois, sur l'arrivée : ménage à faire entre le départ et l'arrivée. */}
          {movement.same_day_turnover && !isLeaving(movement.kind) && <Badge tone="danger">{t('admin.cleaning.turnover')}</Badge>}
        </div>
        {movement.notes && <p className="text-sm">{t('admin.cleaning.notes', { notes: movement.notes })}</p>}
      </div>
    </div>
  )
}
