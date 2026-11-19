/**
 * Graduations « rondes » d'un axe qui part de zéro : 0, 10, 20, 30… couvrant max.
 * Au moins une graduation au-dessus de zéro, même sans donnée.
 */
export function niceTicks(max: number, target = 4): number[] {
  if (max <= 0) return [0, 1]
  const raw = max / target
  const power = 10 ** Math.floor(Math.log10(raw))
  const step = [1, 2, 5, 10].map((factor) => factor * power).find((candidate) => candidate >= raw) ?? 10 * power
  const top = Math.ceil(max / step) * step
  return Array.from({ length: Math.round(top / step) + 1 }, (_, index) => index * step)
}

/** « 2026-10 » → « oct. 26 » (axe) ou « octobre 2026 » (infobulle, tableau). */
export function monthLabel(month: string, style: 'short' | 'long' = 'short'): string {
  const [year = 1970, number = 1] = month.split('-').map(Number)
  const date = new Date(Date.UTC(year, number - 1, 1))
  return new Intl.DateTimeFormat('fr-FR', style === 'short' ? { month: 'short', year: '2-digit', timeZone: 'UTC' } : { month: 'long', year: 'numeric', timeZone: 'UTC' }).format(date)
}
