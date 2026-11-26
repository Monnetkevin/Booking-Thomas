import { http, type HttpClient } from '@/lib/http'
import type { Extra, ExtraCatalog, ExtraInput, ExtraToPrepare } from './types'

export interface ExtraService {
  catalog(): Promise<ExtraCatalog>
  create(input: ExtraInput): Promise<Extra>
  update(id: number, input: ExtraInput): Promise<Extra>
  toPrepare(): Promise<ExtraToPrepare[]>
}

/** Catalogue des options et liste « À préparer », sans React ni cache. */
export function createExtraService(client: HttpClient): ExtraService {
  const unwrap = async <T,>(promise: Promise<{ data: T }>) => (await promise).data

  return {
    catalog: () => client.request('/admin/extras'),
    create: (input) => unwrap(client.request('/admin/extras', { method: 'POST', body: input })),
    update: (id, input) => unwrap(client.request(`/admin/extras/${id}`, { method: 'PUT', body: input })),
    toPrepare: () => unwrap(client.request('/admin/extras/to-prepare')),
  }
}

export const extraService = createExtraService(http)
