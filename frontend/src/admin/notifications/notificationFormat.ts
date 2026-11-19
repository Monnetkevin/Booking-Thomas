/**
 * Mise en forme des notifications, en fonctions pures (testées) : date relative et lien
 * suivi au clic, limité au back-office.
 */

export type RelativeTime =
  | { key: 'now' }
  | { key: 'minutes'; count: number }
  | { key: 'hours'; count: number }
  | { key: 'yesterday' }
  | { key: 'date' }

/** « À l'instant », « il y a 5 min », « il y a 3 h », « hier », puis la date. */
export function relativeTime(isoDateTime: string, now: Date = new Date()): RelativeTime {
  const date = new Date(isoDateTime)
  const minutes = Math.floor((now.getTime() - date.getTime()) / 60_000)
  if (minutes < 1) return { key: 'now' }
  if (minutes < 60) return { key: 'minutes', count: minutes }
  if (minutes < 24 * 60 && date.getDate() === now.getDate()) return { key: 'hours', count: Math.floor(minutes / 60) }

  const yesterday = new Date(now)
  yesterday.setDate(now.getDate() - 1)
  if (date.toDateString() === yesterday.toDateString()) return { key: 'yesterday' }
  return { key: 'date' }
}

/** Lien d'une notification, suivi seulement s'il reste dans le back-office (pas de redirection ouverte). */
export function notificationLink(link: string | null): string | null {
  if (!link || !link.startsWith('/admin') || link.startsWith('//')) return null
  return link
}

/** « 9+ » au-delà de 9 non lues, rien à zéro. */
export function badgeLabel(count: number): string | null {
  if (count <= 0) return null
  return count > 9 ? '9+' : String(count)
}
