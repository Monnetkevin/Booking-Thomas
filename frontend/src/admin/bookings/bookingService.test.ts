import { describe, expect, it, vi } from 'vitest'
import type { HttpClient } from '@/lib/http'
import { bookingQuery, createAdminBookingService } from './bookingService'

describe('service des réservations (admin)', () => {
  it('n’envoie que les filtres renseignés', () => {
    expect(bookingQuery({ status: 'confirmed', search: '', apartment_id: 3 }, 2)).toBe('page=2&status=confirmed&apartment_id=3')
  })

  it('appelle chaque action sur la bonne réservation', async () => {
    const client: HttpClient = { request: vi.fn(async <T,>() => ({ data: {} }) as T) as HttpClient['request'] }
    const service = createAdminBookingService(client)

    await service.get('BT-ABCD2345')
    await service.update('BT-ABCD2345', { housekeeping_notes: 'Lit bébé' })
    await service.cancel('BT-ABCD2345', true)
    await service.resendConfirmation('BT-ABCD2345')

    expect(vi.mocked(client.request).mock.calls).toEqual([
      ['/admin/bookings/BT-ABCD2345'],
      ['/admin/bookings/BT-ABCD2345', { method: 'PATCH', body: { housekeeping_notes: 'Lit bébé' } }],
      ['/admin/bookings/BT-ABCD2345/cancel', { method: 'POST', body: { notify_guest: true } }],
      ['/admin/bookings/BT-ABCD2345/resend-confirmation', { method: 'POST' }],
    ])
    expect(service.pdfUrl('BT-ABCD2345')).toBe('/api/admin/bookings/BT-ABCD2345/pdf')
  })
})
