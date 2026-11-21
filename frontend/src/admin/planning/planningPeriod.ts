import { addDays } from './planningLayout'

export type PlanningView = 'month' | 'week'

export interface PlanningPeriod {
  view: PlanningView
  /** Premier jour affiché. */
  from: string
  /** Nombre de jours affichés. */
  days: number
}

function parts(iso: string): [number, number, number] {
  const [year = 1970, month = 1, day = 1] = iso.split('-').map(Number)
  return [year, month, day]
}

function iso(year: number, month: number, day: number): string {
  return new Date(Date.UTC(year, month - 1, day)).toISOString().slice(0, 10)
}

/** Période (mois civil, ou semaine du lundi au dimanche) qui contient ce jour. */
export function periodContaining(view: PlanningView, day: string): PlanningPeriod {
  const [year, month, date] = parts(day)
  if (view === 'month') {
    const from = iso(year, month, 1)
    const days = new Date(Date.UTC(year, month, 0)).getUTCDate()
    return { view, from, days }
  }
  const weekday = (new Date(Date.UTC(year, month - 1, date)).getUTCDay() + 6) % 7
  return { view, from: addDays(day, -weekday), days: 7 }
}

/** Période précédente (-1) ou suivante (+1). */
export function shiftPeriod(period: PlanningPeriod, step: -1 | 1): PlanningPeriod {
  if (period.view === 'week') return { ...period, from: addDays(period.from, 7 * step) }
  const [year, month] = parts(period.from)
  return periodContaining('month', iso(year, month + step, 1))
}

/** Le jour tombe-t-il dans la période ? */
export function periodIncludes(period: PlanningPeriod, day: string): boolean {
  return day >= period.from && day < addDays(period.from, period.days)
}
