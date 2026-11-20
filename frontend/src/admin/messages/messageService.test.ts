import { describe, expect, it, vi } from 'vitest'
import type { HttpClient } from '@/lib/http'
import { createMessageService } from './messageService'

describe('service des messages', () => {
  it('liste les messages et change leur suivi', async () => {
    const client: HttpClient = { request: vi.fn(async <T,>() => ({ data: {} }) as T) as HttpClient['request'] }
    const service = createMessageService(client)

    await service.list(1, true)
    await service.setRead(4, true)

    expect(vi.mocked(client.request).mock.calls).toEqual([
      ['/admin/messages?page=1&unread=1'],
      ['/admin/messages/4', { method: 'PATCH', body: { read: true } }],
    ])
  })
})
