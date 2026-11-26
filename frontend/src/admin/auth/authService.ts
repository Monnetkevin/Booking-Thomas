import { ApiError } from '@/lib/api'
import { http, type HttpClient } from '@/lib/http'
import type { AuthUser, LoginInput, ResetPasswordInput } from './types'

export interface AuthService {
  /** Utilisateur connecté, ou null s'il n'y a pas de session. */
  currentUser(): Promise<AuthUser | null>
  login(input: LoginInput): Promise<AuthUser>
  logout(): Promise<void>
  /** Mot de passe choisi depuis le lien d'invitation ou de réinitialisation reçu par e-mail. */
  resetPassword(input: ResetPasswordInput): Promise<void>
}

/** Appels d'API de l'authentification, sans React ni cache. */
export function createAuthService(client: HttpClient): AuthService {
  return {
    async currentUser() {
      try {
        return (await client.request<{ data: AuthUser }>('/auth/me')).data
      } catch (error) {
        if (error instanceof ApiError && error.status === 401) return null
        throw error
      }
    },
    async login(input) {
      return (await client.request<{ data: AuthUser }>('/auth/login', { method: 'POST', body: input })).data
    },
    async logout() {
      await client.request<void>('/auth/logout', { method: 'POST' })
    },
    async resetPassword(input) {
      await client.request<void>('/auth/password', { method: 'POST', body: input })
    },
  }
}

export const authService = createAuthService(http)
