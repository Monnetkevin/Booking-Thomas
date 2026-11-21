import type { AssignableRole, Role } from '@/admin/auth/types'

/** Compte du back-office vu par l'administrateur. */
export interface Account {
  id: number
  name: string
  email: string
  role: Role
  is_active: boolean
  /** Le compte connecté lui-même : il ne peut pas se désactiver. */
  is_self: boolean
  /** Dernière connexion réussie (ISO 8601), null si jamais connecté. */
  last_login_at: string | null
}

export interface InviteInput {
  name: string
  email: string
  role: AssignableRole
}
