/** Administrateur, gestionnaire (tout sauf réglages, utilisateurs, tarifs et coupons), ménage. */
export type Role = 'admin' | 'manager' | 'housekeeping'

/** Rôles qu'un administrateur attribue à une invitation ou un changement de rôle. */
export const ASSIGNABLE_ROLES = ['manager', 'housekeeping'] as const satisfies readonly Role[]
export type AssignableRole = (typeof ASSIGNABLE_ROLES)[number]

export interface AuthUser {
  id: number
  name: string
  email: string
  role: Role
  /** Droit de fixer les prix (frais de ménage, total d'une saisie à la main), décidé par l'API. */
  can_manage_pricing: boolean
}

export interface LoginInput {
  email: string
  password: string
  remember: boolean
}

export interface ResetPasswordInput {
  token: string
  email: string
  password: string
  password_confirmation: string
}
