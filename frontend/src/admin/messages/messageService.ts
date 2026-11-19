import { http, type HttpClient } from '@/lib/http'
import type { ContactMessage, ContactMessagePage } from './types'

export interface MessageService {
  list(page: number, unreadOnly: boolean): Promise<ContactMessagePage>
  setRead(id: number, read: boolean): Promise<ContactMessage>
}

/** Messages du formulaire de contact (back-office), sans React ni cache. */
export function createMessageService(client: HttpClient): MessageService {
  return {
    list: (page, unreadOnly) => {
      const query = new URLSearchParams({ page: String(page), ...(unreadOnly ? { unread: '1' } : {}) })
      return client.request(`/admin/messages?${query.toString()}`)
    },
    setRead: async (id, read) =>
      (await client.request<{ data: ContactMessage }>(`/admin/messages/${id}`, { method: 'PATCH', body: { read } })).data,
  }
}

export const messageService = createMessageService(http)
