import { describe, expect, it, vi } from 'vitest'
import type { HttpClient } from '@/lib/http'
import { createAdminReviewService } from './reviewService'

describe('service admin des avis', () => {
  it('filtre la file et envoie chaque décision au bon endpoint', async () => {
    const request = vi.fn(async <T,>() => ({ data: {} }) as T)
    const service = createAdminReviewService({ request } as HttpClient)

    await service.list({ status: 'pending', apartmentId: 3, page: 2 })
    await service.list({ status: 'published', apartmentId: null, page: 1 })
    await service.reject(7, 'Hors sujet')
    await service.reply(7, 'Merci !')
    await service.resendInvitation(8)

    expect(request.mock.calls).toEqual([
      ['/admin/reviews?status=pending&page=2&apartment_id=3'],
      ['/admin/reviews?status=published&page=1'],
      ['/admin/reviews/7/reject', { method: 'POST', body: { reason: 'Hors sujet' } }],
      ['/admin/reviews/7/reply', { method: 'PUT', body: { reply: 'Merci !' } }],
      ['/admin/reviews/8/resend-invitation', { method: 'POST' }],
    ])
  })
})
