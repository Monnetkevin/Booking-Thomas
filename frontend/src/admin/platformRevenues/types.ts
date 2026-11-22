/** Plateformes dont l'admin saisit les revenus (valeurs de l'API). */
export type RevenuePlatform = 'airbnb' | 'booking'

export const REVENUE_PLATFORMS: RevenuePlatform[] = ['airbnb', 'booking']

/** Une case : nuits du mois d'après l'iCal, net versé saisi en centimes (null : pas encore saisi). */
export interface RevenueCell {
  nights: number
  net_amount: number | null
}

/** Feuille d'un mois (« 2026-10 »), une ligne par logement. */
export interface RevenueSheet {
  month: string
  is_current: boolean
  apartments: { id: number; name: string; platforms: Record<RevenuePlatform, RevenueCell> }[]
  totals: Record<RevenuePlatform, number>
  missing: number
  last_saved: { at: string | null; by: string | null } | null
}

/** Un mois de l'historique, le plus récent d'abord. */
export interface RevenueHistoryEntry {
  month: string
  is_current: boolean
  totals: Record<RevenuePlatform, number>
  missing: number
  entered: boolean
}

/** Montant envoyé : null efface la saisie. */
export interface RevenueEntry {
  apartment_id: number
  platform: RevenuePlatform
  net_amount: number | null
}
