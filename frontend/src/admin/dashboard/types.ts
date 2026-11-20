export interface Movement {
  apartment: string | null
  /** Voyageur (réservation) ou plateforme (séjour importé). */
  label: string | null
  reference: string | null
  guests: number | null
}

export interface Dashboard {
  today: string
  movements: Array<{ date: string; arrivals: Movement[]; departures: Movement[] }>
  month: {
    /** Pourcentage de nuits vendues sur les nuits ouvertes, ou null. */
    occupancy: number | null
    nights: { direct: number; platform: number; closed: number }
    /** Encaissé ce mois-ci, en centimes. */
    revenue: number
  }
  alerts: {
    conflicts: Array<{ apartment: string; starts_on: string; ends_on: string }>
    failing_feeds: Array<{ apartment: string; provider: string; empty_refused: boolean; failures: number }>
    unread_messages: number
  }
}
