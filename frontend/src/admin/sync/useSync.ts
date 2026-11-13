import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { syncService, type SyncService } from './syncService'
import type { IcalFeedInput, IcalProvider } from './types'

export const syncKeys = {
  all: ['admin', 'sync'] as const,
  feeds: ['admin', 'sync', 'feeds'] as const,
  logs: (feedId: number) => ['admin', 'sync', 'logs', feedId] as const,
}

/** Calendriers de tous les logements ; l'état se rafraîchit tout seul (lecture toutes les 15 min). */
export function useIcalFeeds(service: SyncService = syncService) {
  return useQuery({ queryKey: syncKeys.feeds, queryFn: () => service.list(), refetchInterval: 60_000 })
}

export function useIcalLogs(feedId: number, enabled: boolean, service: SyncService = syncService) {
  return useQuery({ queryKey: syncKeys.logs(feedId), queryFn: () => service.logs(feedId), enabled })
}

/** Après une écriture : état des calendriers et journaux. */
function useRefresh() {
  const queryClient = useQueryClient()
  return () => queryClient.invalidateQueries({ queryKey: syncKeys.all })
}

/** Enregistre l'URL d'import : crée le calendrier du logement s'il n'existe pas encore. */
export function useSaveIcalFeed(apartmentId: number, service: SyncService = syncService) {
  const refresh = useRefresh()
  return useMutation({
    mutationFn: ({ feedId, provider, input }: { feedId: number | null; provider: IcalProvider; input: IcalFeedInput }) =>
      feedId === null ? service.create(apartmentId, provider, input.import_url ?? null) : service.update(feedId, input),
    onSuccess: refresh,
  })
}

export function useSyncNow(service: SyncService = syncService) {
  const refresh = useRefresh()
  return useMutation({ mutationFn: (feedId: number) => service.syncNow(feedId), onSuccess: refresh })
}

export function useRegenerateExportToken(service: SyncService = syncService) {
  const refresh = useRefresh()
  return useMutation({ mutationFn: (feedId: number) => service.regenerateExportToken(feedId), onSuccess: refresh })
}
