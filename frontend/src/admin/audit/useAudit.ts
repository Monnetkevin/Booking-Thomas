import { keepPreviousData, useQuery } from '@tanstack/react-query'
import { auditService, type AuditService } from './auditService'
import type { AuditAction } from './types'

export function useAuditLogs(page: number, action: AuditAction | null, service: AuditService = auditService) {
  return useQuery({
    queryKey: ['admin', 'audit', page, action] as const,
    queryFn: () => service.list(page, action),
    placeholderData: keepPreviousData,
  })
}
