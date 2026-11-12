/** Dates de séjour « AAAA-MM-JJ » (sans heure ni fuseau). */

function toDate(isoDate: string): Date {
  const [year, month, day] = isoDate.split('-').map(Number)
  return new Date(Date.UTC(year ?? 1970, (month ?? 1) - 1, day ?? 1))
}

/** Date du calendrier (heure locale) → « AAAA-MM-JJ ». */
export function toIso(date: Date): string {
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${date.getFullYear()}-${month}-${day}`
}

/** « AAAA-MM-JJ » → date du calendrier à minuit, heure locale. */
export function fromIso(iso: string): Date {
  const [year, month, day] = iso.split('-').map(Number)
  return new Date(year ?? 1970, (month ?? 1) - 1, day ?? 1)
}

/** « 2026-12-01 » → « 1 déc. 2026 ». */
export function formatDate(isoDate: string, locale = 'fr-FR'): string {
  return new Intl.DateTimeFormat(locale, { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' }).format(
    toDate(isoDate),
  )
}

/** Nombre de nuits entre deux dates, ex. du 1er au 4 → 3. */
export function nightsBetween(from: string, to: string): number {
  return Math.round((toDate(to).getTime() - toDate(from).getTime()) / 86_400_000)
}
