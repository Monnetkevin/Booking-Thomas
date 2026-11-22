import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { platformRevenueService, type PlatformRevenueService } from './platformRevenueService'
import type { RevenueEntry } from './types'

const key = ['admin', 'platform-revenues'] as const

export function useRevenueSheet(month: string, service: PlatformRevenueService = platformRevenueService) {
  return useQuery({ queryKey: [...key, 'sheet', month], queryFn: () => service.sheet(month), placeholderData: keepPreviousData })
}

export function useRevenueHistory(service: PlatformRevenueService = platformRevenueService) {
  return useQuery({ queryKey: [...key, 'history'], queryFn: () => service.history() })
}

/** Une saisie change aussi l'historique, les statistiques et le tableau de bord. */
export function useSaveRevenues(service: PlatformRevenueService = platformRevenueService) {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ month, entries }: { month: string; entries: RevenueEntry[] }) => service.save(month, entries),
    onSuccess: (sheet) => {
      queryClient.setQueryData([...key, 'sheet', sheet.month], sheet)
      return queryClient.invalidateQueries({ queryKey: ['admin'], predicate: (query) => query.queryKey[2] !== 'sheet' })
    },
  })
}
