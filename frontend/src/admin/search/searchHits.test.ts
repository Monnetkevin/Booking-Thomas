import { describe, expect, it, vi } from 'vitest'
import type { HttpClient } from '@/lib/http'
import { apiHits, normalize, pageHits } from './searchHits'
import { createSearchService } from './searchService'

describe('recherche rapide', () => {
  it('compare sans accents ni casse', () => {
    expect(normalize('  L’Élégance ')).toBe('l’elegance')
  })

  it('trouve les pages autorisées par leur libellé', () => {
    const pages = [
      { to: '/admin/planning', label: 'Planning' },
      { to: '/admin/statistiques', label: 'Statistiques' },
    ]
    expect(pageHits('plan', pages).map((hit) => hit.to)).toEqual(['/admin/planning'])
    expect(pageHits('p', pages)).toEqual([])
  })

  it('mène chaque résultat de l’API à sa fiche', () => {
    const hits = apiHits(
      {
        bookings: [{ reference: 'BT-AB12', guest_name: 'Camille', apartment: 'Le Cocon', arrival: '2026-12-01', departure: '2026-12-03', status: 'confirmed' }],
        apartments: [{ id: 3, name: 'Le Cocon', status: 'published' }],
      },
      (arrival, departure) => `${arrival} → ${departure}`,
    )
    expect(hits.map((hit) => hit.to)).toEqual(['/admin/reservations/BT-AB12', '/admin/appartements/3'])
    expect(hits[0]?.detail).toBe('Le Cocon · 2026-12-01 → 2026-12-03')
  })

  it('encode le terme dans l’adresse', async () => {
    const client: HttpClient = { request: vi.fn(async <T,>() => ({ data: { bookings: [], apartments: [] } }) as T) as HttpClient['request'] }
    await createSearchService(client).search('a&b c')
    expect(client.request).toHaveBeenCalledWith('/admin/search?q=a%26b%20c')
  })
})
