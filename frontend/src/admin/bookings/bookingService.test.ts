import { describe, expect, it, vi } from 'vitest'
import type { FileDownloader, HttpClient } from '@/lib/http'
import { bookingQuery, createAdminBookingService } from './bookingService'

describe('service des réservations (admin)', () => {
  it('n’envoie que les filtres renseignés', () => {
    expect(bookingQuery({ status: 'confirmed', search: '', apartment_id: 3 }, 2)).toBe('page=2&status=confirmed&apartment_id=3')
  })

  it('appelle chaque action sur la bonne réservation', async () => {
    const client: HttpClient & FileDownloader = {
      request: vi.fn(async <T,>() => ({ data: {} }) as T) as HttpClient['request'],
      download: vi.fn(async () => ({ blob: new Blob(), filename: null })),
    }
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
    await service.downloadPdf('BT-ABCD2345')
    await service.downloadCsv({ stage: 'upcoming' })
    expect(client.download).toHaveBeenCalledWith('/admin/bookings.csv?page=1&stage=upcoming')
    expect(client.download).toHaveBeenCalledWith('/admin/bookings/BT-ABCD2345/pdf')
  })
})
