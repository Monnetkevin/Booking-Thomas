import type { Overview } from './types'

export interface AlertPart {
  key: 'conflicts' | 'failingFeeds'
  count: number
}

/** Morceaux du résumé du bouton « Alertes » (« 1 conflit · 1 erreur iCal »), sans les zéros. */
export function alertParts(overview: Overview | undefined): AlertPart[] {
  if (!overview) return []
  const parts: AlertPart[] = [
    { key: 'conflicts', count: overview.conflicts },
    { key: 'failingFeeds', count: overview.failing_feeds },
  ]
  return parts.filter((part) => part.count > 0)
}
