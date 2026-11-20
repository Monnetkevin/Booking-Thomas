import { useTranslation } from 'react-i18next'
import { blockKindClasses } from '@/admin/planning/blockColors'
import { addDays, nightKinds, planningDays, type BlockKind } from '@/admin/planning/planningLayout'
import type { Planning } from '@/admin/planning/types'
import { cn } from '@/lib/cn'

const initial = new Intl.DateTimeFormat('fr-FR', { weekday: 'narrow', timeZone: 'UTC' })
const LEGEND: BlockKind[] = ['direct', 'ical', 'closed']

/** Occupation des prochains jours, une ligne par logement, une case par nuit. */
export function OccupancyStrip({ planning }: { planning: Planning }) {
  const { t } = useTranslation()
  const count = Math.round((Date.parse(planning.to) - Date.parse(planning.from)) / 86_400_000)
  const days = planningDays(planning.from, count)

  return (
    <div className="space-y-3">
      {/* relative : les textes sr-only des cases restent dans la zone défilante (sinon la page s'élargit). */}
      <div className="relative overflow-x-auto">
        <table className="w-full min-w-[440px] table-fixed border-separate border-spacing-1 text-xs">
          <thead>
            <tr>
              <th scope="col" className="w-32 sm:w-40">
                <span className="sr-only">{t('admin.planning.apartment')}</span>
              </th>
              {days.map((day) => (
                <th key={day} scope="col" className="font-semibold text-muted" title={day}>
                  {initial.format(new Date(`${day}T00:00:00Z`)).toUpperCase()}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {planning.apartments.map((apartment) => (
              <tr key={apartment.id}>
                <th scope="row" className="truncate pr-2 text-left text-[13px] font-semibold">{apartment.name}</th>
                {nightKinds(apartment, days).map((kind, index) => (
                  <td key={days[index]} className="p-0">
                    <span
                      className={cn('block h-6 rounded-md', kind ? blockKindClasses[kind] : 'bg-white ring-1 ring-line ring-inset')}
                      title={`${days[index]} → ${addDays(days[index] ?? planning.from, 1)} : ${kind ? t(`admin.planning.kinds.${kind}`) : t('admin.dashboard.free')}`}
                    >
                      <span className="sr-only">{kind ? t(`admin.planning.kinds.${kind}`) : t('admin.dashboard.free')}</span>
                    </span>
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <ul className="flex flex-wrap gap-4 text-xs text-muted">
        {LEGEND.map((kind) => (
          <li key={kind} className="flex items-center gap-1.5">
            <span aria-hidden="true" className={cn('size-3 rounded', blockKindClasses[kind])} />
            {t(`admin.planning.kinds.${kind}`)}
          </li>
        ))}
        <li className="flex items-center gap-1.5">
          <span aria-hidden="true" className="size-3 rounded bg-white ring-1 ring-line ring-inset" />
          {t('admin.dashboard.free')}
        </li>
      </ul>
    </div>
  )
}
