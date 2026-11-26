/** Nature d'un mouvement au planning du ménage (même liste que l'API). */
export type CleaningMovementKind = 'departure' | 'arrival' | 'blocked' | 'occupied' | 'occupied_end'

/** Un mouvement : jamais de nom, de coordonnées, de montant ni de source de réservation. */
export interface CleaningMovement {
  apartment_id: number
  apartment: string
  kind: CleaningMovementKind
  /** « 16:00 » ; null si le logement n'a pas d'heure. */
  time: string | null
  /** Nombre de voyageurs, connu pour les réservations du site seulement. */
  guests: number | null
  notes: string | null
  /** Départ et arrivée le même jour dans ce logement. */
  same_day_turnover: boolean
}

export interface CleaningDay {
  date: string
  movements: CleaningMovement[]
}

export interface CleaningSchedule {
  from: string
  to: string
  /** Seulement les jours où il se passe quelque chose. */
  days: CleaningDay[]
}

/** Périodes proposées : 14 jours (par défaut) ou 30. */
export const CLEANING_PERIODS = [14, 30] as const
export type CleaningPeriod = (typeof CLEANING_PERIODS)[number]
