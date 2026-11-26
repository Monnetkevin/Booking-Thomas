import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { initials } from '@/admin/components/initials'
import { cn } from '@/lib/cn'
import { formatDate } from '@/lib/dates'
import type { MovementRow } from '../dashboardFigures'

const weekday = new Intl.DateTimeFormat('fr-FR', { weekday: 'short', day: 'numeric', month: 'short', timeZone: 'UTC' })

function dayLabel(iso: string): string {
  const [year = 1970, month = 1, day = 1] = iso.split('-').map(Number)
  return weekday.format(new Date(Date.UTC(year, month - 1, day)))
}

/** Arrivées et départs à venir : logement, jour, voyageur, sens et source. */
export function MovementList({ rows, today }: { rows: MovementRow[]; today: string }) {
  const { t } = useTranslation()

  if (rows.length === 0) return <p className="text-sm text-muted">{t('admin.dashboard.noMovementAhead')}</p>

  return (
    <ul className="divide-y divide-line">
      {rows.map((row, index) => {
        const { movement } = row
        const who = movement.reference ? (movement.label ?? '') : t('admin.dashboard.platformGuest')
        const content = (
          <>
            <span aria-hidden="true" className="flex size-9 shrink-0 items-center justify-center rounded-full bg-cream text-xs font-bold text-muted">
              {movement.reference && movement.label ? initials(movement.label) : '—'}
            </span>
            <span className="min-w-0 flex-1">
              <span className="block truncate text-sm font-bold">
                {movement.apartment} · {row.date === today ? t('admin.dashboard.today') : dayLabel(row.date)}
              </span>
              <span className="block truncate text-sm text-muted">
                {who}
                {movement.guests !== null && ` · ${t('admin.dashboard.guests', { count: movement.guests })}`}
              </span>
            </span>
            <span className="flex shrink-0 flex-col items-end gap-1">
              <span className={cn('rounded-full px-2 py-0.5 text-xs font-bold', row.kind === 'arrival' ? 'bg-success-soft text-success' : 'bg-cream text-muted')}>
                {t(`admin.dashboard.${row.kind}`)}
              </span>
              <span className="text-xs text-muted">{movement.reference ? t('admin.dashboard.sourceDirect') : movement.label}</span>
            </span>
          </>
        )
        return (
          <li key={`${row.date}-${row.kind}-${index}`} title={formatDate(row.date)}>
            {movement.reference ? (
              <Link to={`/admin/reservations/${movement.reference}`} className="flex items-center gap-3 py-2.5 text-ink no-underline hover:bg-sand">
                {content}
              </Link>
            ) : (
              <div className="flex items-center gap-3 py-2.5">{content}</div>
            )}
          </li>
        )
      })}
    </ul>
  )
}
