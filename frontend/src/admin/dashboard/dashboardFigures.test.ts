import { describe, expect, it } from 'vitest'
import { countDelta, percentDelta, pointsDelta } from '@/admin/components/delta'
import { movementRows, todoItems } from './dashboardFigures'

describe('chiffres du tableau de bord', () => {
  it('calcule les écarts avec la période précédente', () => {
    expect(pointsDelta(72, 65.5)).toEqual({ kind: 'points', value: 6.5 })
    expect(pointsDelta(null, 60)).toBeNull()
    expect(percentDelta(11200, 10000)).toEqual({ kind: 'percent', value: 12 })
    expect(percentDelta(5000, 0)).toBeNull()
    expect(countDelta(18, 15)).toEqual({ kind: 'count', value: 3 })
  })

  it('met à plat arrivées puis départs, jour par jour', () => {
    const movement = { apartment: 'Le Cocon', label: 'Camille', reference: 'BT-1', guests: 2 }
    const rows = movementRows([
      { date: '2026-11-20', arrivals: [movement], departures: [movement] },
      { date: '2026-11-21', arrivals: [], departures: [] },
      { date: '2026-11-22', arrivals: [movement], departures: [] },
    ])
    expect(rows.map((row) => `${row.date} ${row.kind}`)).toEqual(['2026-11-20 arrival', '2026-11-20 departure', '2026-11-22 arrival'])
  })

  it('liste les situations à traiter, conflits d’abord', () => {
    const items = todoItems({
      conflicts: [{ apartment: 'Nid', starts_on: '2026-12-14', ends_on: '2026-12-16' }],
      failing_feeds: [{ apartment: 'Loft', provider: 'Airbnb', empty_refused: false, failures: 4 }],
      unread_messages: 2,
      pending_reviews: 3,
    })
    expect(items.map((item) => item.kind)).toEqual(['conflict', 'failingFeed', 'messages', 'reviews'])
    expect(items.map((item) => item.link)).toEqual(['/admin/planning', '/admin/synchronisation', '/admin/messages', '/admin/avis'])
    expect(items.at(-1)?.count).toBe(3)
  })
})
