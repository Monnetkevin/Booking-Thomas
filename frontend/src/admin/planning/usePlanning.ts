import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { planningService, type PlanningService } from './planningService'

export function usePlanning(from: string, days: number, service: PlanningService = planningService, enabled = true) {
  return useQuery({
    enabled,
    queryKey: ['admin', 'planning', from, days] as const,
    queryFn: () => service.get(from, days),
    placeholderData: keepPreviousData,
    // Un séjour Airbnb peut arriver à tout moment (lecture toutes les 15 minutes).
    refetchInterval: 5 * 60_000,
  })
}
