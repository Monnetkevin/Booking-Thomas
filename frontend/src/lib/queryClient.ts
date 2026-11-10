import { MutationCache, QueryCache, QueryClient } from '@tanstack/react-query'
import { authKeys } from '@/admin/auth/keys'
import { ApiError } from './api'

/**
 * Une réponse 401 sur une autre requête signifie que la session a expiré :
 * on oublie l'utilisateur, et la garde du back-office renvoie vers la connexion.
 */
function onError(error: unknown, queryKey?: readonly unknown[]) {
  const [scope, name] = authKeys.me
  const isMeQuery = queryKey?.[0] === scope && queryKey?.[1] === name
  if (error instanceof ApiError && error.status === 401 && !isMeQuery) {
    queryClient.setQueryData(authKeys.me, null)
  }
}

export const queryClient = new QueryClient({
  queryCache: new QueryCache({ onError: (error, query) => onError(error, query.queryKey) }),
  mutationCache: new MutationCache({ onError: (error) => onError(error) }),
  defaultOptions: {
    queries: {
      staleTime: 60_000,
      // Inutile de réessayer une requête refusée (401, 403, 404, 422…).
      retry: (failureCount, error) =>
        !(error instanceof ApiError && error.status < 500) && failureCount < 2,
      refetchOnWindowFocus: false,
    },
  },
})
