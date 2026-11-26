import { describe, expect, it } from 'vitest'
import type { HttpClient } from '@/lib/http'
import { createBookingService } from './bookingService'
import { bookingStatusView, isAwaitingPayment } from './bookingStatus'

describe('suivi de réservation', () => {
  it('appelle l’API avec la référence et la signature du lien', async () => {
    const calls: string[] = []
    const client: HttpClient = {
      request: async <T>(path: string) => {
        calls.push(path)
        return { data: { reference: 'BT-ABCD2345' } } as T
      },
    }

    const booking = await createBookingService(client).track('BT-ABCD2345', 'a b+c')

    expect(booking.reference).toBe('BT-ABCD2345')
    expect(calls).toEqual(['/bookings/BT-ABCD2345?signature=a+b%2Bc'])
  })

  it('associe un ton à chaque statut', () => {
    expect(bookingStatusView('pending')).toEqual({ tone: 'warning', key: 'tracking.status.pending' })
    expect(bookingStatusView('confirmed').tone).toBe('success')
    expect(bookingStatusView('expired').tone).toBe('danger')
  })

  it('ne rafraîchit que les réservations en attente de paiement', () => {
    expect(isAwaitingPayment('pending')).toBe(true)
    expect(isAwaitingPayment('confirmed')).toBe(false)
  })
})
