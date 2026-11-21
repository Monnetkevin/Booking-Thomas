/** Écart avec la période précédente, affiché par KpiCard. */
export type Delta = { kind: 'points' | 'percent' | 'count'; value: number } | null

/** Écart en points de pourcentage (occupation). */
export function pointsDelta(value: number | null, previous: number | null): Delta {
  if (value === null || previous === null) return null
  return { kind: 'points', value: Math.round((value - previous) * 10) / 10 }
}

/** Écart relatif en % (revenus) ; rien si la période précédente est à zéro. */
export function percentDelta(value: number, previous: number): Delta {
  if (previous === 0) return null
  return { kind: 'percent', value: Math.round(((value - previous) / previous) * 100) }
}

/** Écart absolu (nombre de séjours). */
export function countDelta(value: number, previous: number): Delta {
  return { kind: 'count', value: value - previous }
}
