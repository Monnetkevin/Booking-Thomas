import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { notificationService, type NotificationService } from './notificationService'
import type { NotificationPreferencesInput } from './types'

export const notificationKeys = {
  all: ['admin', 'notifications'] as const,
  unread: ['admin', 'notifications', 'unread'] as const,
  list: (page: number, unreadOnly: boolean) => ['admin', 'notifications', 'list', page, unreadOnly] as const,
  preferences: ['admin', 'notification-preferences'] as const,
}

/** Rafraîchissement du nombre de non lues (spec : toutes les 60 s et au retour sur l'onglet). */
export const UNREAD_REFRESH_MS = 60_000

export function useUnreadCount(service: NotificationService = notificationService) {
  return useQuery({
    queryKey: notificationKeys.unread,
    queryFn: () => service.unreadCount(),
    refetchInterval: UNREAD_REFRESH_MS,
    refetchOnWindowFocus: true,
  })
}

export function useNotificationList(page: number, unreadOnly: boolean, enabled = true, service: NotificationService = notificationService) {
  return useQuery({
    queryKey: notificationKeys.list(page, unreadOnly),
    queryFn: () => service.list(page, unreadOnly),
    placeholderData: keepPreviousData,
    enabled,
  })
}

/** Après une lecture : liste et nombre de non lues. */
function useRefresh() {
  const queryClient = useQueryClient()
  return () => queryClient.invalidateQueries({ queryKey: notificationKeys.all })
}

export function useMarkRead(service: NotificationService = notificationService) {
  const refresh = useRefresh()
  return useMutation({ mutationFn: (id: string) => service.markRead(id), onSuccess: refresh })
}

export function useMarkAllRead(service: NotificationService = notificationService) {
  const refresh = useRefresh()
  return useMutation({ mutationFn: () => service.markAllRead(), onSuccess: refresh })
}

export function useNotificationPreferences(service: NotificationService = notificationService) {
  return useQuery({ queryKey: notificationKeys.preferences, queryFn: () => service.preferences() })
}

export function useUpdateNotificationPreferences(service: NotificationService = notificationService) {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: (input: NotificationPreferencesInput) => service.updatePreferences(input),
    onSuccess: (preferences) => queryClient.setQueryData(notificationKeys.preferences, preferences),
  })
}
