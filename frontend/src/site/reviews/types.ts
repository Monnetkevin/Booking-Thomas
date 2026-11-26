/** Invitation à laisser un avis : le séjour du voyageur et le prénom proposé. */
export interface ReviewInvitation {
  apartment: string
  arrival: string
  departure: string
  nights: number
  first_name: string
  expires_on: string
}

export interface ReviewInput {
  rating: number
  comment: string
  display_name: string
  consent: boolean
}

/** Longueur maximale du commentaire (même limite que l'API). */
export const REVIEW_COMMENT_MAX = 1000

/** Avis publié : prénom, mois du séjour, jamais les dates exactes ni le nom. */
export interface PublishedReview {
  id: number
  rating: number
  comment: string
  display_name: string
  /** « 2026-10 » */
  stay_month: string
  apartment: { name: string; slug: string }
  host_reply: string | null
  published_on: string | null
}

export interface ReviewSummary {
  /** Moyenne arrondie au dixième ; null sans avis. */
  average: number | null
  count: number
  /** Nombre d'avis par note, de 5 à 1. */
  distribution: Record<'1' | '2' | '3' | '4' | '5', number>
}

export interface PublishedReviewPage {
  data: PublishedReview[]
  meta: { current_page: number; last_page: number; total: number }
  summary: ReviewSummary
}
