import { describe, expect, it } from 'vitest'
import { channelShares } from './channels'

describe('répartition par canal', () => {
  it('donne la part de chaque canal non vide', () => {
    const totals = {
      revenue: 462000, platform_revenue: { airbnb: 285000, booking: 0 }, total_revenue: 747000, direct_share: 61.8,
      revenue_per_night: null, nights_direct: 0, nights_platform: 0, average_stay: null, occupancy: null,
    }
    expect(channelShares(totals)).toEqual([
      { key: 'direct', amount: 462000, share: 61.8 },
      { key: 'airbnb', amount: 285000, share: 38.2 },
    ])
  })
})
