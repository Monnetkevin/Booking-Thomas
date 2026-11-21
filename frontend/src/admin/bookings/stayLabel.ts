const dayMonth = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short', timeZone: 'UTC' })
const dayOnly = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', timeZone: 'UTC' })
const full = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' })
const asDate = (iso: string) => new Date(`${iso}T00:00:00Z`)

/** Séjour court : « 4 → 7 nov. », « 28 oct. → 2 nov. », avec l'année si elle change. */
export function stayLabel(arrival: string, departure: string): string {
  if (arrival.slice(0, 4) !== departure.slice(0, 4)) return `${full.format(asDate(arrival))} → ${full.format(asDate(departure))}`
  if (arrival.slice(0, 7) === departure.slice(0, 7)) return `${dayOnly.format(asDate(arrival))} → ${dayMonth.format(asDate(departure))}`
  return `${dayMonth.format(asDate(arrival))} → ${dayMonth.format(asDate(departure))}`
}
