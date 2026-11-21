import { http, type HttpClient } from '@/lib/http'
import type { SearchResults } from './types'

export interface SearchService {
  search(term: string): Promise<SearchResults>
}

/** Recherche rapide du back-office (réservations et logements), sans React ni cache. */
export function createSearchService(client: HttpClient): SearchService {
  return {
    search: async (term) => (await client.request<{ data: SearchResults }>(`/admin/search?q=${encodeURIComponent(term)}`)).data,
  }
}

export const searchService = createSearchService(http)
