import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { api, ApiError } from '@/lib/api'

export type Role = 'admin' | 'housekeeping'

export interface AuthUser {
  id: number
  name: string
  email: string
  role: Role
}

export interface LoginInput {
  email: string
  password: string
  remember: boolean
}

export const authKeys = { me: ['auth', 'me'] as const }

/** Utilisateur connecté, ou null s'il n'y a pas de session. */
export function useCurrentUser() {
  return useQuery({
    queryKey: authKeys.me,
    queryFn: async (): Promise<AuthUser | null> => {
      try {
        return (await api<{ data: AuthUser }>('/auth/me')).data
      } catch (error) {
        if (error instanceof ApiError && error.status === 401) return null
        throw error
      }
    },
    staleTime: 5 * 60_000,
  })
}

export function useLogin() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input: LoginInput) =>
      (await api<{ data: AuthUser }>('/auth/login', { method: 'POST', body: input })).data,
    onSuccess: (user) => queryClient.setQueryData(authKeys.me, user),
  })
}

export function useLogout() {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: () => api<void>('/auth/logout', { method: 'POST' }),
    onSettled: () => {
      // Aucune donnée du back-office ne doit rester en mémoire après la déconnexion.
      queryClient.clear()
      queryClient.setQueryData(authKeys.me, null)
    },
  })
}

/** Cible de redirection après connexion, limitée au back-office (pas de redirection ouverte). */
export function safeAdminRedirect(target: string | null): string {
  if (!target || !target.startsWith('/admin') || target.startsWith('/admin/connexion')) return '/admin'
  return target
}
