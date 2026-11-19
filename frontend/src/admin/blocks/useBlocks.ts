import { useMutation, useQueryClient } from '@tanstack/react-query'
import { blockService, type BlockService, type ManualBlockInput } from './blockService'

/** Une fermeture change le planning, le tableau de bord et les statistiques. */
function useRefresh() {
  const queryClient = useQueryClient()
  return () => queryClient.invalidateQueries({ queryKey: ['admin'] })
}

export function useCreateBlock(service: BlockService = blockService) {
  const refresh = useRefresh()
  return useMutation({
    mutationFn: ({ apartmentId, input }: { apartmentId: number; input: ManualBlockInput }) => service.create(apartmentId, input),
    onSuccess: refresh,
  })
}

export function useRemoveBlock(service: BlockService = blockService) {
  const refresh = useRefresh()
  return useMutation({ mutationFn: (blockId: number) => service.remove(blockId), onSuccess: refresh })
}
