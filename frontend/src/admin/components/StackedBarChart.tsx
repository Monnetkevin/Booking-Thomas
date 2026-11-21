export interface BarColumn {
  key: string
  /** Libellé sous la colonne (axe). */
  label: string
  /** Infobulle de la colonne. */
  title: string
  /** Valeur écrite au-dessus de la colonne, s'il y en a une. */
  caption?: string
  /** Segments empilés, du bas vers le haut. */
  segments: { value: number; className: string }[]
}

interface StackedBarChartProps {
  /** Description complète pour les lecteurs d'écran (toutes les valeurs). */
  label: string
  columns: BarColumn[]
  heightClass?: string
}

/**
 * Colonnes empilées partagées par le tableau de bord et les statistiques : une colonne par
 * mois, hauteur proportionnelle au total, segments colorés par série.
 */
export function StackedBarChart({ label, columns, heightClass = 'h-48' }: StackedBarChartProps) {
  const total = (column: BarColumn) => column.segments.reduce((sum, segment) => sum + segment.value, 0)
  const max = Math.max(1, ...columns.map(total))

  return (
    <figure>
      <div role="img" aria-label={label} className={`flex ${heightClass} items-end gap-1.5 sm:gap-3`}>
        {columns.map((column) => (
          <div key={column.key} className="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-1.5" title={column.title}>
            {column.caption && <span className="text-[11px] font-semibold whitespace-nowrap text-muted tabular-nums">{column.caption}</span>}
            <div className="flex w-full max-w-12 flex-col-reverse overflow-hidden rounded-t-lg" style={{ height: `${Math.max(total(column) > 0 ? 2 : 0, (total(column) / max) * 100)}%` }}>
              {column.segments.map((segment, index) =>
                segment.value > 0 ? <div key={index} className={segment.className} style={{ flexGrow: segment.value }} /> : null,
              )}
            </div>
          </div>
        ))}
      </div>
      <div aria-hidden="true" className="mt-2 flex gap-1.5 border-t border-line pt-2 sm:gap-3">
        {columns.map((column) => (
          <span key={column.key} className="min-w-0 flex-1 truncate text-center text-xs text-muted capitalize">
            {column.label}
          </span>
        ))}
      </div>
    </figure>
  )
}
