export type StatisticsPeriod = 'six_months' | 'year' | 'all'

/** Revenus nets importés de chaque plateforme, en centimes. */
export interface PlatformRevenue {
  airbnb: number
  booking: number
}

export interface MonthStats {
  /** « 2026-10 ». */
  month: string
  nights_direct: number
  nights_platform: number
  /** Revenus directs hors taxe de séjour, en centimes. */
  revenue: number
  platform_revenue: PlatformRevenue
  /** Nuits vendues sur les plateformes sans revenu importé pour ce mois. */
  platform_revenue_missing: boolean
  occupancy: number | null
}

export interface Statistics {
  period: StatisticsPeriod
  from: string
  to: string
  totals: {
    revenue: number
    platform_revenue: PlatformRevenue
    total_revenue: number
    /** Part des revenus directs, en %, ou null sans revenu. */
    direct_share: number | null
    /** Revenu par nuit vendue (tous canaux), null si les revenus des plateformes manquent. */
    revenue_per_night: number | null
    nights_direct: number
    nights_platform: number
    average_nightly_price: number | null
    average_stay: number | null
    occupancy: number | null
  }
  months: MonthStats[]
  apartments: Array<{ id: number; name: string; nights_direct: number; nights_platform: number; revenue: number; platform_revenue: number; occupancy: number | null; rating: number | null }>
}
