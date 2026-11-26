import type { SearchCriteria } from './types'

/**
 * Dates et voyageurs dans l'URL (partageable) :
 * /logements?arrivee=2026-12-04&depart=2026-12-07&voyageurs=2
 * /logements/le-cocon?arrivee=…&depart=…&adultes=2&enfants=1
 */
const ISO = /^\d{4}-\d{2}-\d{2}$/

function validDate(value: string | null): string | null {
  return value && ISO.test(value) ? value : null
}

function count(value: string | null, min: number, max: number, fallback: number): number {
  const parsed = Number.parseInt(value ?? '', 10)
  return Number.isNaN(parsed) ? fallback : Math.min(max, Math.max(min, parsed))
}

/** Séjour saisi valable : deux dates, départ après l'arrivée. */
export function isValidStay(arrival: string, departure: string): boolean {
  return ISO.test(arrival) && ISO.test(departure) && departure > arrival
}

/** Critères de recherche complets et cohérents, ou null. */
export function searchFromParams(params: URLSearchParams): SearchCriteria | null {
  const arrival = validDate(params.get('arrivee'))
  const departure = validDate(params.get('depart'))
  if (!arrival || !departure || departure <= arrival) return null
  return { arrival, departure, guests: count(params.get('voyageurs'), 1, 20, 2) }
}

export function searchToParams(criteria: SearchCriteria): URLSearchParams {
  return new URLSearchParams({ arrivee: criteria.arrival, depart: criteria.departure, voyageurs: String(criteria.guests) })
}

export interface StaySelection {
  arrival: string | null
  departure: string | null
  adults: number
  children: number
}

/** Séjour en cours de saisie sur une fiche (les voyageurs de la recherche deviennent des adultes). */
export function stayFromParams(params: URLSearchParams): StaySelection {
  const arrival = validDate(params.get('arrivee'))
  const departure = validDate(params.get('depart'))
  return {
    arrival,
    departure: arrival && departure && departure > arrival ? departure : null,
    adults: count(params.get('adultes') ?? params.get('voyageurs'), 1, 20, 2),
    children: count(params.get('enfants'), 0, 20, 0),
  }
}

export function stayToParams(stay: StaySelection): URLSearchParams {
  const params = new URLSearchParams()
  if (stay.arrival) params.set('arrivee', stay.arrival)
  if (stay.departure) params.set('depart', stay.departure)
  params.set('adultes', String(stay.adults))
  if (stay.children > 0) params.set('enfants', String(stay.children))
  return params
}
