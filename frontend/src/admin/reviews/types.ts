export type ReviewStatus = 'invited' | 'pending' | 'published' | 'rejected'

/** Ordre des filtres de la file de modération (« à modérer » d'abord). */
export const REVIEW_STATUSES: ReviewStatus[] = ['pending', 'published', 'rejected', 'invited']

/** Avis, ou invitation encore sans réponse (note et texte vides). */
export interface AdminReview {
  id: number
  status: ReviewStatus
  rating: number | null
  comment: string | null
  display_name: string | null
  /** Motif interne d'un refus, jamais publié. */
  rejection_reason: string | null
  host_reply: string | null
  apartment: { id: number; name: string }
  booking: { reference: string; guest_name: string; arrival: string; departure: string }
  invited_at: string
  expires_at: string
  submitted_at: string | null
  published_at: string | null
}

export interface AdminReviewPage {
  data: AdminReview[]
  meta: { current_page: number; last_page: number; total: number; counts: Record<ReviewStatus, number>; summary: ReviewSummary }
}

/** Chiffres de l'écran : moyenne et nombre d'avis publiés, à modérer, part des 5 étoiles, part des invitations des 30 derniers jours qui ont reçu un avis. */
export interface ReviewSummary {
  average: number | null
  count: number
  pending: number
  five_star_share: number | null
  reply_rate: number | null
}

export interface ReviewFilters {
  status: ReviewStatus
  apartmentId: number | null
  page: number
}
