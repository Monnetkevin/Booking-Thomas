import { useTranslation } from 'react-i18next'
import { cn } from '@/lib/cn'
import { blockKindClasses } from '../blockColors'
import { blockKind, placeBlock } from '../planningLayout'
import type { PlanningApartment, PlanningBlock } from '../types'

interface PlanningGridProps {
  days: string[]
  today: string
  apartments: PlanningApartment[]
  onBlock: (block: PlanningBlock, apartment: PlanningApartment) => void
  onEmptyDay: (apartment: PlanningApartment, day: string) => void
}

const weekday = new Intl.DateTimeFormat('fr-FR', { weekday: 'short', timeZone: 'UTC' })
const dayNumber = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', timeZone: 'UTC' })
const longDay = new Intl.DateTimeFormat('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', timeZone: 'UTC' })
const asDate = (iso: string) => new Date(`${iso}T00:00:00Z`)

/**
 * Grille logements × jours : une ligne par logement, une colonne par nuit. Chaque séjour
 * est une barre cliquable ; une case libre ouvre la fermeture de dates. Défile à
 * l'horizontale sur téléphone, la colonne des logements restant visible.
 */
export function PlanningGrid({ days, today, apartments, onBlock, onEmptyDay }: PlanningGridProps) {
  const { t } = useTranslation()
  const columns = { gridTemplateColumns: `repeat(${days.length}, minmax(2.75rem, 1fr))` }

  return (
    <div className="overflow-x-auto rounded-[var(--radius-card)] border border-border bg-white">
      <div className="min-w-max">
        <div className="flex border-b border-border">
          <div className="sticky left-0 z-20 w-36 shrink-0 bg-white px-3 py-2 text-sm font-semibold sm:w-44">{t('admin.planning.apartment')}</div>
          <div className="grid flex-1" style={columns}>
            {days.map((day) => {
              const weekend = [0, 6].includes(asDate(day).getUTCDay())
              return (
                <div
                  key={day}
                  className={cn('border-l border-line px-1 py-1.5 text-center text-xs', weekend && 'bg-sand', day === today && 'bg-accent-soft font-bold')}
                >
                  <span className="block text-muted">{weekday.format(asDate(day))}</span>
                  <span className="block text-sm">{dayNumber.format(asDate(day))}</span>
                </div>
              )
            })}
          </div>
        </div>

        {apartments.map((apartment) => (
          <div key={apartment.id} className="flex border-b border-line last:border-b-0">
            <div className="sticky left-0 z-20 flex w-36 shrink-0 items-center bg-white px-3 py-2 text-sm font-semibold sm:w-44">{apartment.name}</div>
            <div className="relative grid flex-1" style={{ ...columns, gridTemplateRows: 'minmax(3.25rem, auto)' }}>
              {days.map((day, index) => (
                <button
                  key={day}
                  type="button"
                  style={{ gridColumn: index + 1, gridRow: 1 }}
                  className={cn('border-l border-line hover:bg-sand focus-visible:relative focus-visible:z-10', day === today && 'bg-accent-soft/40')}
                  aria-label={t('admin.planning.closeFrom', { apartment: apartment.name, day: longDay.format(asDate(day)) })}
                  onClick={() => onEmptyDay(apartment, day)}
                />
              ))}
              {apartment.blocks.map((block) => {
                const placement = days[0] ? placeBlock(block, days[0], days.length) : null
                if (!placement) return null
                const label = block.label ?? t(`admin.planning.kinds.${blockKind(block)}`)
                return (
                  <button
                    key={block.id}
                    type="button"
                    style={{ gridColumn: `${placement.start + 1} / span ${placement.span}`, gridRow: 1 }}
                    className={cn(
                      'z-10 mx-0.5 my-1.5 truncate rounded-lg px-2 text-left text-xs font-semibold',
                      blockKindClasses[blockKind(block)],
                      block.has_conflict && 'ring-2 ring-danger ring-offset-1',
                      block.has_conflict && 'self-end',
                      placement.continuesBefore && 'rounded-l-none',
                      placement.continuesAfter && 'rounded-r-none',
                    )}
                    title={`${label} · ${block.starts_on} → ${block.ends_on}${block.has_conflict ? ` · ${t('admin.planning.conflict')}` : ''}`}
                    onClick={() => onBlock(block, apartment)}
                  >
                    {block.has_conflict && <span aria-hidden="true">⚠ </span>}
                    {label}
                    {block.has_conflict && <span className="sr-only"> ({t('admin.planning.conflict')})</span>}
                  </button>
                )
              })}
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}
