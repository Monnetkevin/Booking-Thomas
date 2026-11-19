export interface MonthStats {
  /** « 2026-10 ». */
  month: string
  nights_direct: number
  nights_platform: number
  /** Revenus directs hors taxe de séjour, en centimes. */
  revenue: number
  occupancy: number | null
}

export interface Statistics {
  from: string
  to: string
  totals: {
    revenue: number
    nights_direct: number
    nights_platform: number
    average_nightly_price: number | null
    average_stay: number | null
    occupancy: number | null
  }
  months: MonthStats[]
  apartments: Array<{ id: number; name: string; nights_direct: number; nights_platform: number; revenue: number; occupancy: number | null; rating: number | null }>
}
