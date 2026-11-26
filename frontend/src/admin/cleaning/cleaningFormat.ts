import { addDays } from '@/admin/planning/planningLayout'
import type { CleaningMovementKind } from './types'

/** « 16:00 » → « 16 h », « 10:30 » → « 10 h 30 ». */
export function formatHour(time: string): string {
  const [hour, minute] = time.split(':').map(Number)
  return minute ? `${hour} h ${String(minute).padStart(2, '0')}` : `${hour} h`
}

/** Aujourd'hui, demain, ou une autre date. */
export function relativeDay(date: string, today: string): 'today' | 'tomorrow' | 'later' {
  if (date === today) return 'today'
  return date === addDays(today, 1) ? 'tomorrow' : 'later'
}

/** Le logement se libère (ménage à faire) ou s'occupe (prêt avant l'heure). */
export function isLeaving(kind: CleaningMovementKind): boolean {
  return kind === 'departure' || kind === 'occupied_end'
}
