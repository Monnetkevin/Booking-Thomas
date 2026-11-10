import type { AvailabilityCalendar } from './types'

/**
 * Règles du calendrier, en fonctions pures sur des dates « AAAA-MM-JJ ». Elles guident la
 * saisie ; le devis côté serveur reste seul juge.
 */

export function isoDate(date: Date): string {
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${date.getFullYear()}-${month}-${day}`
}

export function fromIso(iso: string): Date {
  const [year, month, day] = iso.split('-').map(Number)
  return new Date(year ?? 1970, (month ?? 1) - 1, day ?? 1)
}

export function addDays(iso: string, days: number): string {
  const date = fromIso(iso)
  date.setDate(date.getDate() + days)
  return isoDate(date)
}

/** Jour ISO : 1 = lundi … 7 = dimanche. */
function isoDay(iso: string): number {
  return ((fromIso(iso).getDay() + 6) % 7) + 1
}

export interface StayCalendarRules {
  minStayFor(arrival: string): number
  isNightFree(night: string): boolean
  canArrive(date: string): boolean
  canDepart(arrival: string, departure: string): boolean
}

export function stayCalendarRules(calendar: AvailabilityCalendar): StayCalendarRules {
  const unavailable = new Set(calendar.unavailable)
  const periodFor = (date: string) => calendar.periods.find((p) => date >= p.starts_on && date <= p.ends_on)

  const isNightFree = (night: string) => night >= calendar.from && night < calendar.to && !unavailable.has(night)
  const minStayFor = (arrival: string) => periodFor(arrival)?.min_stay ?? calendar.min_stay

  /** Les nuits [arrival, arrival + n[ sont-elles toutes libres ? */
  const freeNights = (arrival: string, nights: number) => {
    for (let i = 0; i < nights; i++) if (!isNightFree(addDays(arrival, i))) return false
    return true
  }

  const canArrive = (date: string) => {
    if (date < calendar.first_arrival || date > calendar.last_arrival) return false
    const days = periodFor(date)?.arrival_days
    if (days && !days.includes(isoDay(date))) return false
    return freeNights(date, minStayFor(date))
  }

  const canDepart = (arrival: string, departure: string) => {
    if (departure <= arrival) return false
    const nights = Math.round((fromIso(departure).getTime() - fromIso(arrival).getTime()) / 86_400_000)
    if (nights < minStayFor(arrival) || nights > calendar.max_stay) return false
    // La nuit du départ peut être prise : seule compte la dernière nuit du séjour.
    return freeNights(arrival, nights)
  }

  return { minStayFor, isNightFree, canArrive, canDepart }
}
