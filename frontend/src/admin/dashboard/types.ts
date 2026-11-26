export interface Movement {
  apartment: string | null
  /** Voyageur (réservation) ou plateforme (séjour importé). */
  label: string | null
  reference: string | null
  guests: number | null
}

export type DashboardPeriod = 'week' | 'month' | 'year'

export interface Dashboard {
  today: string
  period: { key: DashboardPeriod; from: string; to: string }
  figures: {
    /** Pourcentage de nuits vendues sur les nuits ouvertes, tous canaux, ou null. */
    occupancy: { value: number | null; previous: number | null }
    /** Revenus des réservations du site arrivant dans la période, hors taxe de séjour, en centimes. */
    revenue: { value: number; previous: number }
    /** Séjours arrivant dans la période (site et plateformes), dont réservés sur le site. */
    stays: { value: number; direct: number; previous: number }
  }
  /** Revenus directs des six derniers mois, au mois d'arrivée. */
  revenue_months: Array<{ month: string; revenue: number }>
  movements: Array<{ date: string; arrivals: Movement[]; departures: Movement[] }>
  alerts: {
    conflicts: Array<{ apartment: string; starts_on: string; ends_on: string }>
    failing_feeds: Array<{ apartment: string; provider: string; empty_refused: boolean; failures: number }>
    unread_messages: number
    /** Avis déposés, à publier ou refuser. */
    pending_reviews: number
  }
  /** Note moyenne des avis publiés (null sans avis). */
  reviews: { average: number | null; count: number }
}
