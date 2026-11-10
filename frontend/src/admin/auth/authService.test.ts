import { describe, expect, it, vi } from 'vitest'
import { ApiError } from '@/lib/api'
import type { HttpClient } from '@/lib/http'
import { createAuthService } from './authService'

const user = { id: 1, name: 'Thomas', email: 'hote@example.com', role: 'admin' as const }

function fakeClient(impl: HttpClient['request']): HttpClient {
  return { request: vi.fn(impl) as HttpClient['request'] }
}

describe('service d’authentification', () => {
  it('renvoie l’utilisateur connecté', async () => {
    const client = fakeClient(async <T,>() => ({ data: user }) as T)

    await expect(createAuthService(client).currentUser()).resolves.toEqual(user)
    expect(client.request).toHaveBeenCalledWith('/auth/me')
  })

  it('renvoie null sans session (401)', async () => {
    const client = fakeClient(async () => {
      throw new ApiError(401, null)
    })

    await expect(createAuthService(client).currentUser()).resolves.toBeNull()
  })

  it('laisse remonter les autres erreurs', async () => {
    const client = fakeClient(async () => {
      throw new ApiError(500, null)
    })

    await expect(createAuthService(client).currentUser()).rejects.toBeInstanceOf(ApiError)
  })

  it('envoie les identifiants en POST', async () => {
    const client = fakeClient(async <T,>() => ({ data: user }) as T)
    const input = { email: 'hote@example.com', password: 'secret', remember: true }

    await expect(createAuthService(client).login(input)).resolves.toEqual(user)
    expect(client.request).toHaveBeenCalledWith('/auth/login', { method: 'POST', body: input })
  })
})
