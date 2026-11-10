import type { ApartmentStatus } from './types'

export const apartmentKeys = {
  all: ['admin', 'apartments'] as const,
  list: (status?: ApartmentStatus) => ['admin', 'apartments', 'list', status ?? 'all'] as const,
  detail: (id: number) => ['admin', 'apartments', 'detail', id] as const,
  amenities: ['admin', 'amenities'] as const,
}
