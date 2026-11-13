import type { BadgeTone } from '@/components/ui/Badge'
import type { IcalFeed, IcalSyncLog } from './types'

/**
 * État d'un calendrier vu par l'hôte :
 * - not_configured : aucun calendrier pour ce logement ;
 * - export_only : URL du site donnée à la plateforme, mais rien n'est importé ;
 * - disabled : import arrêté par l'hôte (les nuits déjà importées restent bloquées) ;
 * - pending : URL renseignée, première lecture pas encore faite ;
 * - failing : dernière lecture en erreur ;
 * - ok : dernière lecture réussie.
 */
export type FeedHealth = 'not_configured' | 'export_only' | 'disabled' | 'pending' | 'failing' | 'ok'

export function feedHealth(feed: IcalFeed | undefined): FeedHealth {
  if (!feed) return 'not_configured'
  if (!feed.import_url) return 'export_only'
  if (!feed.is_active) return 'disabled'
  if (feed.last_status === null) return 'pending'
  return feed.last_status === 'failed' ? 'failing' : 'ok'
}

export const healthTone: Record<FeedHealth, BadgeTone> = {
  not_configured: 'neutral',
  export_only: 'warning',
  disabled: 'neutral',
  pending: 'accent',
  failing: 'danger',
  ok: 'success',
}

export type SyncChange = 'created' | 'updated' | 'removed' | 'conflicts'

/** Changements d'une lecture, dans l'ordre d'affichage, sans les compteurs à zéro. */
export function syncChanges(log: IcalSyncLog): Array<{ change: SyncChange; count: number }> {
  const changes: SyncChange[] = ['created', 'updated', 'removed', 'conflicts']
  return changes.map((change) => ({ change, count: log[change] })).filter(({ count }) => count > 0)
}
