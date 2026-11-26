import { describe, expect, it, vi } from 'vitest'
import type { HttpClient } from '@/lib/http'
import { createUserService } from './userService'

function fakeClient(): HttpClient {
  return { request: vi.fn(async <T,>() => ({ data: {} }) as T) as HttpClient['request'] }
}

describe('service des comptes', () => {
  it('liste, invite avec un rôle, change le rôle, active et envoie un lien de mot de passe', async () => {
    const client = fakeClient()
    const service = createUserService(client)

    await service.list()
    await service.invite({ name: 'Alice', email: 'alice@example.com', role: 'housekeeping' })
    await service.setRole(5, 'housekeeping')
    await service.setActive(5, false)
    await service.sendPasswordReset(5)

    expect(vi.mocked(client.request).mock.calls).toEqual([
      ['/admin/users'],
      ['/admin/users', { method: 'POST', body: { name: 'Alice', email: 'alice@example.com', role: 'housekeeping' } }],
      ['/admin/users/5', { method: 'PATCH', body: { role: 'housekeeping' } }],
      ['/admin/users/5', { method: 'PATCH', body: { is_active: false } }],
      ['/admin/users/5/password-reset', { method: 'POST' }],
    ])
  })
})
