import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { platformRevenueService, type PlatformRevenueService } from './platformRevenueService'
import type { RevenueEntry } from './types'

const key = ['admin', 'platform-revenues'] as const

/**
 * month null : mois par défaut choisi par le serveur. Sa feuille est aussi rangée sous son mois :
 * quand la page inscrit ce mois dans l'adresse, elle la retrouve sans nouveau chargement.
 */
export function useRevenueSheet(month: string | null, service: PlatformRevenueService = platformRevenueService) {
  const queryClient = useQueryClient()
  return useQuery({
    queryKey: [...key, 'sheet', month ?? 'default'],
    // Feuille tout juste chargée (ou rangée sous son mois) : pas de rechargement immédiat.
    staleTime: 30_000,
    queryFn: async () => {
      const sheet = await service.sheet(month)
      if (month === null) queryClient.setQueryData([...key, 'sheet', sheet.month], sheet)
      return sheet
    },
  })
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
