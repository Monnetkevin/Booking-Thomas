import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { cleaningService, type CleaningService } from './cleaningService'
import type { CleaningPeriod } from './types'

export function useCleaningSchedule(days: CleaningPeriod, service: CleaningService = cleaningService) {
  return useQuery({
    queryKey: ['admin', 'cleaning-schedule', days] as const,
    queryFn: () => service.schedule(days),
    placeholderData: keepPreviousData,
    // Un séjour Airbnb peut arriver à tout moment, et l'écran reste ouvert sur le téléphone.
    refetchInterval: 5 * 60_000,
    refetchOnWindowFocus: true,
  })
}
