import { describe, expect, it, vi } from 'vitest'
import type { HttpClient } from '@/lib/http'
import { createReviewService } from './reviewService'

describe('service des avis', () => {
  it('lit l’invitation puis dépose l’avis avec le jeton du lien', async () => {
    const invitation = { apartment: 'Le Cocon', arrival: '2026-11-16', departure: '2026-11-19', nights: 3, first_name: 'Camille', expires_on: '2026-12-20' }
    const request = vi.fn(async <T,>(path: string) => (path.endsWith('abc') ? { data: invitation } : { message: 'Merci' }) as T)
    const service = createReviewService({ request } as HttpClient)

    expect(await service.invitation('abc')).toEqual(invitation)
    await service.submit('abc', { rating: 5, comment: 'Parfait, merci.', display_name: 'Camille', consent: true })

    expect(request).toHaveBeenNthCalledWith(1, '/review-invitations/abc')
    expect(request).toHaveBeenNthCalledWith(2, '/review-invitations/abc', {
      method: 'POST',
      body: { rating: 5, comment: 'Parfait, merci.', display_name: 'Camille', consent: true },
    })
  })
})
