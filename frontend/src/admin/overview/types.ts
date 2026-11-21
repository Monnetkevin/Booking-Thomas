/** Compteurs de la barre du back-office (GET /admin/overview). */
export interface Overview {
  conflicts: number
  failing_feeds: number
  unread_messages: number
  /** Dernière lecture réussie d'un calendrier (ISO 8601), null si aucune. */
  last_sync_at: string | null
}
