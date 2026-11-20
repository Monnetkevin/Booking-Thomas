import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { MIN_SEARCH_LENGTH } from './searchHits'
import { searchService, type SearchService } from './searchService'

/** Résultats de l'API pour un terme d'au moins 2 caractères (déjà temporisé par l'appelant). */
export function useSearch(term: string, service: SearchService = searchService) {
  const trimmed = term.trim()
  return useQuery({
    queryKey: ['admin', 'search', trimmed] as const,
    queryFn: () => service.search(trimmed),
    enabled: trimmed.length >= MIN_SEARCH_LENGTH,
    placeholderData: keepPreviousData,
    staleTime: 30_000,
  })
}
