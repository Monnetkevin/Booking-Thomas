import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { messageService, type MessageService } from './messageService'

export const messageKeys = {
  all: ['admin', 'messages'] as const,
  list: (page: number, unreadOnly: boolean) => ['admin', 'messages', page, unreadOnly] as const,
}

export function useMessages(page: number, unreadOnly: boolean, service: MessageService = messageService) {
  return useQuery({
    queryKey: messageKeys.list(page, unreadOnly),
    queryFn: () => service.list(page, unreadOnly),
    placeholderData: keepPreviousData,
  })
}

export function useSetMessageRead(service: MessageService = messageService) {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: ({ id, read }: { id: number; read: boolean }) => service.setRead(id, read),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: messageKeys.all }),
  })
}
