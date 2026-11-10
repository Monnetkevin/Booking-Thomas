/** Jours ISO : 1 = lundi … 7 = dimanche. */
export type IsoDay = 1 | 2 | 3 | 4 | 5 | 6 | 7

export interface RatePeriod {
  id: number
  apartment_id: number
  label: string | null
  /** Dates incluses, AAAA-MM-JJ. */
  starts_on: string
  ends_on: string
  /** Centimes. */
  nightly_price: number
  min_stay: number | null
  weekend_supplement: number | null
  /** null = arrivée possible tous les jours. */
  arrival_days: IsoDay[] | null
}

export type RatePeriodInput = Omit<RatePeriod, 'id' | 'apartment_id'>
