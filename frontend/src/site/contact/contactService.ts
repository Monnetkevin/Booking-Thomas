import { http, type HttpClient } from '@/lib/http'

export interface ContactInput {
  name: string
  email: string
  phone: string
  message: string
  apartment: string | null
  captcha_token: string | null
  /** Champ piège : toujours vide pour un humain. */
  website: string
}

export interface ContactService {
  send(input: ContactInput): Promise<{ message: string }>
}

export function createContactService(client: HttpClient): ContactService {
  return { send: (input) => client.request('/contact', { method: 'POST', body: input }) }
}

export const contactService = createContactService(http)
