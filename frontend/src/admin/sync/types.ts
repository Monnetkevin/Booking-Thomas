/** Plateforme dont le calendrier est synchronisé. */
export type IcalProvider = 'airbnb' | 'booking'

/** Plateformes proposées pour chaque logement, dans l'ordre d'affichage. */
export const ICAL_PROVIDERS: IcalProvider[] = ['airbnb', 'booking']

/** Issue d'une lecture : flux appliqué, identique au précédent, ou erreur (rien n'est libéré). */
export type IcalSyncStatus = 'success' | 'unchanged' | 'failed'

export interface IcalFeed {
  id: number
  apartment_id: number
  provider: IcalProvider
  provider_label: string
  /** URL d'export du calendrier chez la plateforme, lue par le site. */
  import_url: string | null
  /** URL du site à coller chez la plateforme (« Importer le calendrier »). */
  export_url: string
  is_active: boolean
  /** Instants ISO 8601. */
  last_synced_at: string | null
  last_succeeded_at: string | null
  last_status: IcalSyncStatus | null
  last_error: string | null
  consecutive_failures: number
  /** Dernière lecture refusée car le calendrier était vide : l'hôte peut confirmer qu'il l'est vraiment. */
  empty_calendar_refused: boolean
}

export interface IcalSyncLog {
  id: number
  feed_id: number
  /** Journal de tous les calendriers seulement. */
  apartment?: string | null
  provider?: string
  trigger: 'scheduled' | 'manual'
  status: IcalSyncStatus
  /** Séjours ajoutés, déplacés, libérés ; conflits en cours ; événements aux dates illisibles (laissés tels quels). */
  created: number
  updated: number
  removed: number
  conflicts: number
  unreadable: number
  error: string | null
  duration_ms: number
  synced_at: string
}

export interface IcalFeedInput {
  import_url?: string | null
  is_active?: boolean
}
