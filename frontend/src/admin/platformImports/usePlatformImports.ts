import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { platformImportService, type PlatformImportService } from './platformImportService'
import type { PayoutPlatform } from './types'

const historyKey = ['admin', 'platform-imports'] as const

export function useImportHistory(service: PlatformImportService = platformImportService) {
  return useQuery({ queryKey: historyKey, queryFn: () => service.history() })
}

export function usePreviewImport(service: PlatformImportService = platformImportService) {
  return useMutation({ mutationFn: ({ platform, file }: { platform: PayoutPlatform; file: File }) => service.preview(platform, file) })
}

/** Un import change aussi les statistiques et le tableau de bord. */
export function useRunImport(service: PlatformImportService = platformImportService) {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ platform, file, mapping }: { platform: PayoutPlatform; file: File; mapping: Record<string, number> }) => service.import(platform, file, mapping),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['admin'] }),
  })
}
