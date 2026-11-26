import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { extraService, type ExtraService } from './extraService'
import type { ExtraInput } from './types'

const extraKeys = {
  all: ['admin', 'extras'] as const,
  catalog: ['admin', 'extras', 'catalog'] as const,
  toPrepare: ['admin', 'extras', 'to-prepare'] as const,
}

export function useExtraCatalog(service: ExtraService = extraService) {
  return useQuery({ queryKey: extraKeys.catalog, queryFn: () => service.catalog() })
}

export function useExtrasToPrepare(service: ExtraService = extraService) {
  return useQuery({ queryKey: extraKeys.toPrepare, queryFn: () => service.toPrepare() })
}

/** Création (id null) ou modification d'une option ; le catalogue est relu ensuite. */
export function useSaveExtra(service: ExtraService = extraService) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ id, input }: { id: number | null; input: ExtraInput }) => (id === null ? service.create(input) : service.update(id, input)),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: extraKeys.all }),
  })
}
