import { http, type HttpClient } from '@/lib/http'
import type { PublishedReviewPage, ReviewInput, ReviewInvitation } from './types'

export interface ReviewService {
  /** Invitation reçue par e-mail ; le jeton est la seule preuve du séjour. */
  invitation(token: string): Promise<ReviewInvitation>
  submit(token: string, input: ReviewInput): Promise<{ message: string }>
  /** Avis publiés d'un logement (slug) ou de tous, avec la moyenne. */
  published(apartment: string | null, page: number): Promise<PublishedReviewPage>
}

export function createReviewService(client: HttpClient): ReviewService {
  const path = (token: string) => `/review-invitations/${encodeURIComponent(token)}`

  return {
    invitation: async (token) => (await client.request<{ data: ReviewInvitation }>(path(token))).data,
    submit: (token, input) => client.request(path(token), { method: 'POST', body: input }),
    published: (apartment, page) => client.request(`/reviews?${new URLSearchParams({ page: String(page), ...(apartment ? { apartment } : {}) })}`),
  }
}

export const reviewService = createReviewService(http)
