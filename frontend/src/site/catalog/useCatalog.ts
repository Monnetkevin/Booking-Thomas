import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { useEffect, useState } from 'react'
import type { StayRequest } from '@/lib/pricing/types'
import { useLocale } from '@/i18n/useLocale'
import { catalogService, type CatalogService } from './catalogService'
import type { SearchCriteria } from './types'

/** Les réponses dépendent de la langue (textes traduits) : elle fait partie des clés de cache. */
export const catalogKeys = {
  list: (locale: string) => ['catalog', locale, 'list'] as const,
  detail: (locale: string, slug: string) => ['catalog', locale, 'detail', slug] as const,
  calendar: (slug: string) => ['catalog', 'calendar', slug] as const,
  search: (locale: string, criteria: SearchCriteria) => ['catalog', locale, 'search', criteria] as const,
  quote: (locale: string, slug: string, stay: StayRequest) => ['catalog', locale, 'quote', slug, stay] as const,
}

export function useApartments(service: CatalogService = catalogService) {
  const { locale } = useLocale()
  return useQuery({ queryKey: catalogKeys.list(locale), queryFn: () => service.list() })
}

export function useApartmentDetail(slug: string, service: CatalogService = catalogService) {
  const { locale } = useLocale()
  return useQuery({ queryKey: catalogKeys.detail(locale, slug), queryFn: () => service.get(slug) })
}

export function useAvailabilityCalendar(slug: string, service: CatalogService = catalogService) {
  return useQuery({ queryKey: catalogKeys.calendar(slug), queryFn: () => service.calendar(slug), staleTime: 60_000 })
}

export function useSearch(criteria: SearchCriteria | null, service: CatalogService = catalogService) {
  const { locale } = useLocale()
  return useQuery({
    queryKey: catalogKeys.search(locale, criteria ?? { arrival: '', departure: '', guests: 0 }),
    queryFn: () => service.search(criteria as SearchCriteria),
    enabled: criteria !== null,
    staleTime: 30_000,
  })
}

/** Valeur stabilisée après `delay` ms sans changement (appels d'API différés). */
export function useDebounced<T>(value: T, delay = 300): T {
  const [debounced, setDebounced] = useState(value)
  useEffect(() => {
    const timer = setTimeout(() => setDebounced(value), delay)
    return () => clearTimeout(timer)
  }, [value, delay])
  return debounced
}

/** Devis recalculé par le serveur, 300 ms après le dernier changement de dates ou de voyageurs. */
export function useQuote(slug: string, stay: StayRequest | null, service: CatalogService = catalogService) {
  const { locale } = useLocale()
  const debounced = useDebounced(stay)

  return useQuery({
    queryKey: catalogKeys.quote(locale, slug, debounced ?? { arrival: '', departure: '', adults: 0, children: 0 }),
    queryFn: () => service.quote(slug, debounced as StayRequest),
    enabled: debounced !== null,
    placeholderData: keepPreviousData,
    retry: false,
    staleTime: 30_000,
  })
}
