import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { couponService, type CouponService } from './couponService'
import type { CouponInput } from './types'

const couponKeys = { all: ['admin', 'coupons'] as const }

export function useCoupons(service: CouponService = couponService) {
  return useQuery({ queryKey: couponKeys.all, queryFn: () => service.list() })
}

/** Création (id null) ou modification ; la liste est relue ensuite. */
export function useSaveCoupon(service: CouponService = couponService) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: ({ id, input }: { id: number | null; input: CouponInput }) => (id === null ? service.create(input) : service.update(id, input)),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: couponKeys.all }),
  })
}
