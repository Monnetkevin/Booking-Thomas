import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { saveFile } from '@/lib/saveFile'
import { adminBookingService, type AdminBookingService } from './bookingService'
import type { BookingFilters, GuestInput, ManualBookingInput } from './types'

export const bookingKeys = {
  all: ['admin', 'bookings'] as const,
  list: (filters: BookingFilters, page: number) => ['admin', 'bookings', 'list', filters, page] as const,
  detail: (reference: string) => ['admin', 'bookings', 'detail', reference] as const,
}

export function useBookingList(filters: BookingFilters, page: number, service: AdminBookingService = adminBookingService) {
  return useQuery({ queryKey: bookingKeys.list(filters, page), queryFn: () => service.list(filters, page), placeholderData: keepPreviousData })
}

export function useBooking(reference: string, service: AdminBookingService = adminBookingService) {
  return useQuery({ queryKey: bookingKeys.detail(reference), queryFn: () => service.get(reference) })
}

/** Une réservation modifiée change aussi le planning, le tableau de bord et les statistiques. */
function useRefreshAdmin() {
  const queryClient = useQueryClient()
  return () => queryClient.invalidateQueries({ queryKey: ['admin'] })
}

export function useUpdateBooking(reference: string, service: AdminBookingService = adminBookingService) {
  const refresh = useRefreshAdmin()
  return useMutation({ mutationFn: (input: GuestInput) => service.update(reference, input), onSuccess: refresh })
}

export function useCancelBooking(reference: string, service: AdminBookingService = adminBookingService) {
  const refresh = useRefreshAdmin()
  return useMutation({ mutationFn: (notifyGuest: boolean) => service.cancel(reference, notifyGuest), onSuccess: refresh })
}

export function useResendConfirmation(reference: string, service: AdminBookingService = adminBookingService) {
  return useMutation({ mutationFn: () => service.resendConfirmation(reference) })
}

export function useDownloadBookingPdf(reference: string, service: AdminBookingService = adminBookingService) {
  return useMutation({ mutationFn: async () => saveFile(await service.downloadPdf(reference), `${reference}.pdf`) })
}

export function useCreateBooking(service: AdminBookingService = adminBookingService) {
  const refresh = useRefreshAdmin()
  return useMutation({ mutationFn: (input: ManualBookingInput) => service.create(input), onSuccess: refresh })
}
