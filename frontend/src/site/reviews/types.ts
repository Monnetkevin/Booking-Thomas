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
