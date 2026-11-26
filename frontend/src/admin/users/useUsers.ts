import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import type { AssignableRole } from '@/admin/auth/types'
import type { InviteInput } from './types'
import { userService, type UserService } from './userService'

export const userKeys = { all: ['admin', 'users'] as const }

export function useAccounts(service: UserService = userService) {
  return useQuery({ queryKey: userKeys.all, queryFn: () => service.list() })
}

function useRefresh() {
  const queryClient = useQueryClient()
  return () => queryClient.invalidateQueries({ queryKey: userKeys.all })
}

export function useInviteAccount(service: UserService = userService) {
  const refresh = useRefresh()
  return useMutation({ mutationFn: (input: InviteInput) => service.invite(input), onSuccess: refresh })
}

export function useSetAccountActive(service: UserService = userService) {
  const refresh = useRefresh()
  return useMutation({
    mutationFn: ({ id, active }: { id: number; active: boolean }) => service.setActive(id, active),
    onSuccess: refresh,
  })
}

export function useSetAccountRole(service: UserService = userService) {
  const refresh = useRefresh()
  return useMutation({
    mutationFn: ({ id, role }: { id: number; role: AssignableRole }) => service.setRole(id, role),
    onSuccess: refresh,
  })
}

export function useSendPasswordReset(service: UserService = userService) {
  return useMutation({ mutationFn: (id: number) => service.sendPasswordReset(id) })
}
