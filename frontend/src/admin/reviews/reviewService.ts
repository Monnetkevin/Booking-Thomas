import { http, type HttpClient } from '@/lib/http'
import type { AdminReview, AdminReviewPage, ReviewFilters } from './types'

export interface AdminReviewService {
  list(filters: ReviewFilters): Promise<AdminReviewPage>
  publish(id: number): Promise<AdminReview>
  reject(id: number, reason: string): Promise<AdminReview>
  reply(id: number, reply: string): Promise<AdminReview>
  resendInvitation(id: number): Promise<AdminReview>
}

/** File de modération des avis, sans React ni cache. */
export function createAdminReviewService(client: HttpClient): AdminReviewService {
  const unwrap = async (promise: Promise<{ data: AdminReview }>) => (await promise).data

  return {
    list: ({ status, apartmentId, page }) => {
      const query = new URLSearchParams({ status, page: String(page), ...(apartmentId ? { apartment_id: String(apartmentId) } : {}) })
      return client.request(`/admin/reviews?${query.toString()}`)
    },
    publish: (id) => unwrap(client.request(`/admin/reviews/${id}/publish`, { method: 'POST' })),
    reject: (id, reason) => unwrap(client.request(`/admin/reviews/${id}/reject`, { method: 'POST', body: { reason } })),
    reply: (id, reply) => unwrap(client.request(`/admin/reviews/${id}/reply`, { method: 'PUT', body: { reply } })),
    resendInvitation: (id) => unwrap(client.request(`/admin/reviews/${id}/resend-invitation`, { method: 'POST' })),
  }
}

export const adminReviewService = createAdminReviewService(http)
