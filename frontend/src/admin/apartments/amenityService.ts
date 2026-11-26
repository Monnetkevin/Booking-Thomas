import { http, type HttpClient } from '@/lib/http'
import type { Amenity, Apartment } from './types'

export interface AmenityService {
  catalog(): Promise<Amenity[]>
  sync(apartmentId: number, amenityIds: number[]): Promise<Apartment>
}

export function createAmenityService(client: HttpClient): AmenityService {
  const unwrap = async <T>(promise: Promise<{ data: T }>) => (await promise).data

  return {
    catalog: () => unwrap(client.request('/admin/amenities')),
    sync: (apartmentId, amenityIds) =>
      unwrap(
        client.request(`/admin/apartments/${apartmentId}/amenities`, {
          method: 'PUT',
          body: { amenity_ids: amenityIds },
        }),
      ),
  }
}

export const amenityService = createAmenityService(http)
