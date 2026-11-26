import { http, type HttpClient } from '@/lib/http'
import type { ReviewInput, ReviewInvitation } from './types'

export interface ReviewService {
  /** Invitation reçue par e-mail ; le jeton est la seule preuve du séjour. */
  invitation(token: string): Promise<ReviewInvitation>
  submit(token: string, input: ReviewInput): Promise<{ message: string }>
}

export function createReviewService(client: HttpClient): ReviewService {
  const path = (token: string) => `/review-invitations/${encodeURIComponent(token)}`

  return {
    invitation: async (token) => (await client.request<{ data: ReviewInvitation }>(path(token))).data,
    submit: (token, input) => client.request(path(token), { method: 'POST', body: input }),
  }
}

export const reviewService = createReviewService(http)
