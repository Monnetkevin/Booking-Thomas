import type { SearchHit, SearchResults } from './types'

export const MIN_SEARCH_LENGTH = 2

/** Texte comparable : minuscules, sans accents. */
export function normalize(text: string): string {
  return text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLocaleLowerCase('fr-FR').trim()
}

/** Pages du back-office dont le libellé contient le terme (la liste vient du menu autorisé). */
export function pageHits(term: string, pages: { to: string; label: string }[]): SearchHit[] {
  const needle = normalize(term)
  if (needle.length < MIN_SEARCH_LENGTH) return []
  return pages.filter((page) => normalize(page.label).includes(needle)).map((page) => ({ id: `page:${page.to}`, kind: 'page', title: page.label, to: page.to }))
}

/** Réservations puis logements renvoyés par l'API, chacun avec la page qui l'ouvre. */
export function apiHits(results: SearchResults | undefined, describeStay: (arrival: string, departure: string) => string): SearchHit[] {
  if (!results) return []
  return [
    ...results.bookings.map((booking): SearchHit => ({
      id: `booking:${booking.reference}`,
      kind: 'booking',
      title: `${booking.guest_name} · ${booking.reference}`,
      detail: [booking.apartment, describeStay(booking.arrival, booking.departure)].filter(Boolean).join(' · '),
      to: `/admin/reservations/${encodeURIComponent(booking.reference)}`,
    })),
    ...results.apartments.map((apartment): SearchHit => ({
      id: `apartment:${apartment.id}`,
      kind: 'apartment',
      title: apartment.name,
      to: `/admin/appartements/${apartment.id}`,
    })),
  ]
}
