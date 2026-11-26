import type { Statistics } from './types'

/** Couleurs des canaux, communes au graphique, à la légende et à la répartition. */
export const CHANNEL_CLASSES = { direct: 'bg-accent', airbnb: 'bg-[#2a78d6]', booking: 'bg-night-soft' } as const

export interface ChannelShare {
  key: 'direct' | 'airbnb' | 'booking'
  amount: number
  /** Part du total, en %. */
  share: number
}

/** Répartition des revenus par canal (direct, Airbnb, Booking.com), sans les canaux vides. */
export function channelShares(totals: Statistics['totals']): ChannelShare[] {
  const amounts: ChannelShare['key'][] = ['direct', 'airbnb', 'booking']
  const value = (key: ChannelShare['key']) => (key === 'direct' ? totals.revenue : totals.platform_revenue[key])
  const total = totals.total_revenue
  return amounts
    .map((key) => ({ key, amount: value(key), share: total > 0 ? Math.round((value(key) / total) * 1000) / 10 : 0 }))
    .filter((channel) => channel.amount > 0)
}
