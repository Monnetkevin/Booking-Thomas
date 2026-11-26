import { useId, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { monthLabel, niceTicks } from '../chartScale'
import type { MonthStats } from '../types'

/**
 * Couleurs validées (contrastes, daltonisme) : terre cuite pour le direct, bleu pour les
 * plateformes. Le back-office n'a pas de mode sombre.
 */
const SERIES = { direct: '#c05630', platform: '#2a78d6' } as const

const WIDTH = 720
const HEIGHT = 260
const MARGIN = { top: 20, right: 8, bottom: 28, left: 36 }
const BAR = 24
const GAP = 2
const RADIUS = 4

/** Colonne arrondie en haut seulement (4 px), carrée sur la ligne de base. */
function topRounded(x: number, y: number, width: number, height: number): string {
  const r = Math.min(RADIUS, height, width / 2)
  return `M${x},${y + height}V${y + r}Q${x},${y} ${x + r},${y}H${x + width - r}Q${x + width},${y} ${x + width},${y + r}V${y + height}Z`
}

/**
 * Nuits vendues par mois, empilées : en direct (bas) et sur les plateformes (haut). Total
 * écrit au-dessus de chaque colonne ; détail au survol ou au clavier. Le tableau des mois,
 * sous le graphique, en donne toutes les valeurs.
 */
export function NightsChart({ months }: { months: MonthStats[] }) {
  const { t } = useTranslation()
  const [active, setActive] = useState<number | null>(null)
  const titleId = useId()
  const max = Math.max(0, ...months.map((month) => month.nights_direct + month.nights_platform))
  const ticks = niceTicks(max)
  const top = ticks[ticks.length - 1] ?? 1
  const plotWidth = WIDTH - MARGIN.left - MARGIN.right
  const plotHeight = HEIGHT - MARGIN.top - MARGIN.bottom
  const band = plotWidth / Math.max(1, months.length)
  const y = (value: number) => MARGIN.top + plotHeight - (value / top) * plotHeight
  const activeMonth = active === null ? null : months[active]

  return (
    <figure className="relative">
      <ul className="mb-3 flex flex-wrap gap-x-5 gap-y-1 text-sm" aria-label={t('admin.statistics.legend')}>
        <li className="flex items-center gap-2">
          <span aria-hidden="true" className="inline-block size-3 rounded-sm" style={{ background: SERIES.direct }} />
          {t('admin.statistics.direct')}
        </li>
        <li className="flex items-center gap-2">
          <span aria-hidden="true" className="inline-block size-3 rounded-sm" style={{ background: SERIES.platform }} />
          {t('admin.statistics.platform')}
        </li>
      </ul>
      <svg viewBox={`0 0 ${WIDTH} ${HEIGHT}`} className="h-auto w-full" role="img" aria-labelledby={titleId}>
        <title id={titleId}>{t('admin.statistics.chartTitle')}</title>
        {ticks.map((tick) => (
          <g key={tick}>
            <line x1={MARGIN.left} x2={WIDTH - MARGIN.right} y1={y(tick)} y2={y(tick)} stroke="var(--color-line)" strokeWidth={1} />
            <text x={MARGIN.left - 6} y={y(tick)} textAnchor="end" dominantBaseline="middle" className="fill-muted text-[11px]">
              {tick}
            </text>
          </g>
        ))}
        {months.map((month, index) => {
          const x = MARGIN.left + index * band + (band - BAR) / 2
          const total = month.nights_direct + month.nights_platform
          const directTop = y(month.nights_direct)
          const directHeight = MARGIN.top + plotHeight - directTop
          const platformTop = y(total)
          // Écart de 2 px (couleur du fond) entre les deux segments.
          const platformHeight = Math.max(0, directTop - platformTop - (month.nights_direct > 0 ? GAP : 0))
          return (
            <g
              key={month.month}
              tabIndex={0}
              role="button"
              aria-label={t('admin.statistics.barLabel', { month: monthLabel(month.month, 'long'), direct: month.nights_direct, platform: month.nights_platform })}
              onMouseEnter={() => setActive(index)}
              onMouseLeave={() => setActive(null)}
              onFocus={() => setActive(index)}
              onBlur={() => setActive(null)}
              className="outline-none focus-visible:[&>rect:first-child]:stroke-ink"
            >
              {/* Zone de survol plus large que la colonne. */}
              <rect x={MARGIN.left + index * band} y={MARGIN.top} width={band} height={plotHeight} fill={active === index ? 'var(--color-sand)' : 'transparent'} strokeWidth={1} />
              {month.nights_direct > 0 && (
                <path d={month.nights_platform > 0 ? `M${x},${directTop + directHeight}V${directTop}H${x + BAR}V${directTop + directHeight}Z` : topRounded(x, directTop, BAR, directHeight)} fill={SERIES.direct} />
              )}
              {platformHeight > 0 && <path d={topRounded(x, platformTop, BAR, platformHeight)} fill={SERIES.platform} />}
              {total > 0 && (
                <text x={x + BAR / 2} y={platformTop - 5} textAnchor="middle" className="fill-ink text-[11px] font-semibold">
                  {total}
                </text>
              )}
              <text x={x + BAR / 2} y={HEIGHT - 8} textAnchor="middle" className="fill-muted text-[11px]">
                {monthLabel(month.month)}
              </text>
            </g>
          )
        })}
      </svg>
      {activeMonth && active !== null && (
        <div
          role="status"
          className="pointer-events-none absolute top-8 rounded-lg border border-border bg-white px-3 py-2 text-sm shadow-lg"
          style={{ left: `min(calc(${((MARGIN.left + active * band + band / 2) / WIDTH) * 100}% - 4rem), calc(100% - 9rem))` }}
        >
          <p className="font-semibold">{monthLabel(activeMonth.month, 'long')}</p>
          <p className="flex items-center gap-2">
            <span aria-hidden="true" className="inline-block size-2.5 rounded-sm" style={{ background: SERIES.direct }} />
            {t('admin.statistics.directNights', { count: activeMonth.nights_direct })}
          </p>
          <p className="flex items-center gap-2">
            <span aria-hidden="true" className="inline-block size-2.5 rounded-sm" style={{ background: SERIES.platform }} />
            {t('admin.statistics.platformNights', { count: activeMonth.nights_platform })}
          </p>
        </div>
      )}
    </figure>
  )
}
