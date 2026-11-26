/** Détail du prix renvoyé par l'API (POST /quotes, aperçu de l'hôte). Montants en centimes. */
export interface QuoteLine {
  type: 'accommodation' | 'cleaning' | 'tourist_tax' | string
  label: string
  amount: number
  quantity?: number
  unit_amount?: number
}

export interface Quote {
  nights: number
  lines: QuoteLine[]
  accommodation: number
  cleaning_fee: number
  tourist_tax: number
  total: number
  currency: 'EUR'
}

/** Règle du séjour non respectée (séjour minimum, dates prises…), message déjà traduit. */
export interface StayViolation {
  code: string
  message: string
}

export interface StayRequest {
  arrival: string
  departure: string
  adults: number
  children: number
}
