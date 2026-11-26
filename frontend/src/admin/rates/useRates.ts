import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { apartmentKeys } from '@/admin/apartments/keys'
import { rateService, type RateService } from './rateService'
import type { StayRequest } from '@/lib/pricing/types'
import type { RatePeriodInput } from './types'

export const rateKeys = {
  apartment: (apartmentId: number) => ['admin', 'rates', apartmentId] as const,
  list: (apartmentId: number, includePast: boolean) => ['admin', 'rates', apartmentId, includePast] as const,
}

export function useRatePeriods(apartmentId: number | null, includePast: boolean, service: RateService = rateService) {
  return useQuery({
    queryKey: rateKeys.list(apartmentId ?? 0, includePast),
    queryFn: () => service.list(apartmentId as number, includePast),
    enabled: apartmentId !== null,
  })
}

/** Après une écriture : périodes du logement et « prix actuel » de la liste des logements. */
function useRefresh(apartmentId: number) {
  const queryClient = useQueryClient()
  return () =>
    Promise.all([
      queryClient.invalidateQueries({ queryKey: rateKeys.apartment(apartmentId) }),
      queryClient.invalidateQueries({ queryKey: apartmentKeys.all }),
    ])
}

export function useSaveRatePeriod(apartmentId: number, service: RateService = rateService) {
  const refresh = useRefresh(apartmentId)
  return useMutation({
    mutationFn: ({ id, input }: { id: number | null; input: RatePeriodInput }) =>
      id === null ? service.create(apartmentId, input) : service.update(id, input),
    onSuccess: refresh,
  })
}

export function useDeleteRatePeriod(apartmentId: number, service: RateService = rateService) {
  const refresh = useRefresh(apartmentId)
  return useMutation({ mutationFn: (id: number) => service.remove(id), onSuccess: refresh })
}

/** Aperçu du prix d'un séjour test (écran Tarifs). */
export function useQuotePreview(apartmentId: number, service: RateService = rateService) {
  return useMutation({ mutationFn: (stay: StayRequest) => service.preview(apartmentId, stay) })
}
