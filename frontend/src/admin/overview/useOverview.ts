import { useQuery } from '@tanstack/react-query'
import { overviewService, type OverviewService } from './overviewService'

/** Compteurs relus chaque minute ; désactivés pour le ménage (l'API les refuse). */
export function useOverview(enabled: boolean, service: OverviewService = overviewService) {
  return useQuery({ queryKey: ['admin', 'overview'] as const, queryFn: () => service.get(), enabled, refetchInterval: 60_000 })
}
