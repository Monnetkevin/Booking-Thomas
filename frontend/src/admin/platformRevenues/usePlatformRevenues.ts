import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { platformRevenueService, type PlatformRevenueService } from './platformRevenueService'
import type { RevenueEntry } from './types'

const key = ['admin', 'platform-revenues'] as const

/** month null : mois par défaut choisi par le serveur. */
export function useRevenueSheet(month: string | null, service: PlatformRevenueService = platformRevenueService) {
  return useQuery({ queryKey: [...key, 'sheet', month ?? 'default'], queryFn: () => service.sheet(month) })
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
      // Le formulaire, gardé par mois, n'est pas remis à zéro par ce rechargement.
      return queryClient.invalidateQueries({ queryKey: ['admin'] })
    },
  })
}
