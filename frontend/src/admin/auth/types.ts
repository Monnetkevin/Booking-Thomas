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
