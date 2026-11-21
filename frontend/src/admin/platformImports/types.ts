export type PayoutPlatform = 'airbnb' | 'booking'

export interface PreviewRow {
  line: number
  /** Un versement (ligne de l'export) ; un long séjour en a plusieurs au même code. */
  entry_key: string
  confirmation_code: string
  listing: string
  guest: string | null
  arrival: string
  departure: string
  gross_amount: number | null
  fees: number | null
  net_amount: number
  already_imported: boolean
}

export interface ImportPreview {
  rows: PreviewRow[]
  skipped: Array<{ line: number; reason: string }>
  /** Réservations annulées chez la plateforme, déjà importées : retirées à l'import. */
  cancelled: number
  /** Annonces du fichier et le logement auquel chacune est déjà rattachée (null : à choisir). */
  listings: Array<{ listing: string; apartment_id: number | null }>
}

export interface PlatformImport {
  id: number
  platform: PayoutPlatform
  filename: string
  by: string | null
  rows_created: number
  rows_updated: number
  rows_skipped: number
  rows_removed: number
  created_at: string
}
