import { useMutation, useQuery } from '@tanstack/react-query'
import { useLocale } from '@/i18n/useLocale'
import { reviewService, type ReviewService } from './reviewService'
import type { ReviewInput } from './types'

export function useReviewInvitation(token: string, service: ReviewService = reviewService) {
  const { locale } = useLocale()

  return useQuery({
    queryKey: ['review-invitation', locale, token],
    queryFn: () => service.invitation(token),
    // Un lien expiré ou déjà utilisé (410) ne se corrige pas en réessayant.
    retry: false,
  })
}

export function useSubmitReview(token: string, service: ReviewService = reviewService) {
  return useMutation({ mutationFn: (input: ReviewInput) => service.submit(token, input) })
}
