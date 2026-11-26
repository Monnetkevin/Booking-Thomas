import { http, type HttpClient } from '@/lib/http'
import type { AuditAction, AuditLogPage } from './types'

export interface AuditService {
  list(page: number, action: AuditAction | null): Promise<AuditLogPage>
}

/** Journal des actions sensibles (lecture seule), sans React ni cache. */
export function createAuditService(client: HttpClient): AuditService {
  return {
    list: (page, action) => {
      const query = new URLSearchParams({ page: String(page), ...(action ? { action } : {}) })
      return client.request(`/admin/audit-logs?${query.toString()}`)
    },
  }
}

export const auditService = createAuditService(http)
