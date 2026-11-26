import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { adminReviewService, type AdminReviewService } from './reviewService'
import type { ReviewFilters } from './types'

const reviewKeys = {
  all: ['admin', 'reviews'] as const,
  list: (filters: ReviewFilters) => ['admin', 'reviews', filters] as const,
}

export function useAdminReviews(filters: ReviewFilters, service: AdminReviewService = adminReviewService) {
  return useQuery({ queryKey: reviewKeys.list(filters), queryFn: () => service.list(filters), placeholderData: keepPreviousData })
}

/** Une décision change la file, ses nombres et le compteur « avis à modérer » de la barre. */
export function useReviewAction<T>(action: (service: AdminReviewService, input: T) => Promise<unknown>, service: AdminReviewService = adminReviewService) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (input: T) => action(service, input),
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: reviewKeys.all }),
        queryClient.invalidateQueries({ queryKey: ['admin', 'overview'] }),
      ])
    },
  })
}
