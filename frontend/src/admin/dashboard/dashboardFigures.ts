import type { Dashboard, Movement } from './types'

export interface MovementRow {
  date: string
  kind: 'arrival' | 'departure'
  movement: Movement
}

/** Arrivées et départs à plat, dans l'ordre des jours (arrivées d'abord). */
export function movementRows(movements: Dashboard['movements']): MovementRow[] {
  return movements.flatMap((day) => [
    ...day.arrivals.map((movement): MovementRow => ({ date: day.date, kind: 'arrival', movement })),
    ...day.departures.map((movement): MovementRow => ({ date: day.date, kind: 'departure', movement })),
  ])
}

export type TodoKind = 'conflict' | 'failingFeed' | 'emptyFeed' | 'messages' | 'reviews'

export interface TodoItem {
  kind: TodoKind
  key: string
  apartment?: string
  provider?: string
  from?: string
  to?: string
  count?: number
  link: string
}

/** Situations à traiter, dans l'ordre d'urgence : conflits, calendriers, messages, avis. */
export function todoItems(alerts: Dashboard['alerts']): TodoItem[] {
  return [
    ...alerts.conflicts.map((conflict, index): TodoItem => ({
      kind: 'conflict',
      key: `conflict-${index}`,
      apartment: conflict.apartment,
      from: conflict.starts_on,
      to: conflict.ends_on,
      link: '/admin/planning',
    })),
    ...alerts.failing_feeds.map((feed, index): TodoItem => ({
      kind: feed.empty_refused ? 'emptyFeed' : 'failingFeed',
      key: `feed-${index}`,
      apartment: feed.apartment,
      provider: feed.provider,
      count: feed.failures,
      link: '/admin/synchronisation',
    })),
    ...(alerts.unread_messages > 0 ? [{ kind: 'messages' as const, key: 'messages', count: alerts.unread_messages, link: '/admin/messages' }] : []),
    ...(alerts.pending_reviews > 0 ? [{ kind: 'reviews' as const, key: 'reviews', count: alerts.pending_reviews, link: '/admin/avis' }] : []),
  ]
}
