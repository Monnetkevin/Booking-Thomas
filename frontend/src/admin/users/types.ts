import type { Role } from '@/admin/auth/types'

/** Compte du back-office vu par l'administrateur. */
export interface Account {
  id: number
  name: string
  email: string
  role: Role
  is_active: boolean
  /** Le compte connecté lui-même : il ne peut pas se désactiver. */
  is_self: boolean
}

export interface InviteInput {
  name: string
  email: string
}
