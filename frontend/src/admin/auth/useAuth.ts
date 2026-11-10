import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { authService, type AuthService } from './authService'
import { authKeys } from './keys'
import type { LoginInput } from './types'

/** Hooks React Query de la session ; le service est injectable pour les tests. */
export function useCurrentUser(service: AuthService = authService) {
  return useQuery({
    queryKey: authKeys.me,
    queryFn: () => service.currentUser(),
    staleTime: 5 * 60_000,
  })
}

export function useLogin(service: AuthService = authService) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: (input: LoginInput) => service.login(input),
    onSuccess: (user) => queryClient.setQueryData(authKeys.me, user),
  })
}

export function useLogout(service: AuthService = authService) {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: () => service.logout(),
    onSettled: () => {
      // Aucune donnée du back-office ne doit rester en mémoire après la déconnexion.
      queryClient.clear()
      queryClient.setQueryData(authKeys.me, null)
    },
  })
}
