import { http, type HttpClient } from '@/lib/http'
import type { Account, InviteInput } from './types'

export interface UserService {
  list(): Promise<Account[]>
  invite(input: InviteInput): Promise<Account>
  setActive(id: number, active: boolean): Promise<Account>
  sendPasswordReset(id: number): Promise<void>
}

/** Comptes du back-office (invitation du ménage, activation), sans React ni cache. */
export function createUserService(client: HttpClient): UserService {
  const unwrap = async <T>(promise: Promise<{ data: T }>) => (await promise).data

  return {
    list: () => unwrap(client.request('/admin/users')),
    invite: (input) => unwrap(client.request('/admin/users', { method: 'POST', body: input })),
    setActive: (id, active) => unwrap(client.request(`/admin/users/${id}`, { method: 'PATCH', body: { is_active: active } })),
    sendPasswordReset: (id) => client.request<void>(`/admin/users/${id}/password-reset`, { method: 'POST' }),
  }
}

export const userService = createUserService(http)
