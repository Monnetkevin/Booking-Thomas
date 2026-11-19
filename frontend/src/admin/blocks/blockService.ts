import { http, type HttpClient } from '@/lib/http'

export interface ManualBlockInput {
  starts_on: string
  ends_on: string
  note: string | null
}

export interface BlockService {
  create(apartmentId: number, input: ManualBlockInput): Promise<void>
  remove(blockId: number): Promise<void>
}

/** Fermetures manuelles d'un logement, sans React ni cache. */
export function createBlockService(client: HttpClient): BlockService {
  return {
    create: async (apartmentId, input) => {
      await client.request(`/admin/apartments/${apartmentId}/blocks`, { method: 'POST', body: input })
    },
    remove: (blockId) => client.request<void>(`/admin/blocks/${blockId}`, { method: 'DELETE' }),
  }
}

export const blockService = createBlockService(http)
