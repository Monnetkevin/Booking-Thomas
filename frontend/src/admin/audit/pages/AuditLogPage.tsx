import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { PageHeader } from '@/admin/components/PageHeader'
import { ErrorState } from '@/components/feedback/ErrorState'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { SelectField } from '@/components/ui/Field'
import { Spinner } from '@/components/ui/Spinner'
import { formatDateTime } from '@/lib/dates'
import { auditChanges } from '../auditChanges'
import { AUDIT_ACTIONS, type AuditAction } from '../types'
import { useAuditLogs } from '../useAudit'

/** Journal des actions sensibles : qui, quoi, quand, et ce qui a changé. Lecture seule. */
export function AuditLogPage() {
  const { t } = useTranslation()
  const [action, setAction] = useState<AuditAction | null>(null)
  const [page, setPage] = useState(1)
  const logs = useAuditLogs(page, action)

  return (
    <>
      <PageHeader title={t('admin.audit.title')} subtitle={t('admin.audit.subtitle')} />
      <SelectField
        className="mb-5 max-w-sm"
        label={t('admin.audit.filter')}
        value={action ?? ''}
        onChange={(value) => {
          setAction(value === '' ? null : (value as AuditAction))
          setPage(1)
        }}
        options={[
          { value: '', label: t('admin.audit.allActions') },
          ...AUDIT_ACTIONS.map((value) => ({ value, label: t(`admin.audit.actions.${value}`) })),
        ]}
      />
      {logs.isPending ? (
        <Spinner label={t('common.loading')} />
      ) : logs.isError ? (
        <ErrorState onRetry={() => void logs.refetch()} />
      ) : logs.data.data.length === 0 ? (
        <Alert tone="info">{t('admin.audit.empty')}</Alert>
      ) : (
        <Card className="p-0">
          <ul className="divide-y divide-border">
            {logs.data.data.map((entry) => {
              const changes = auditChanges(entry.before, entry.after)
              return (
                <li key={entry.id} className="px-5 py-4">
                  <p className="font-semibold">{t(`admin.audit.actions.${entry.action}`)}</p>
                  <p className="text-sm text-muted">
                    {formatDateTime(entry.created_at)} · {entry.user ?? t('admin.audit.system')}
                    {entry.subject_id && ` · ${entry.subject_type} #${entry.subject_id}`}
                    {entry.ip && ` · ${entry.ip}`}
                  </p>
                  {changes.length > 0 && (
                    <dl className="mt-2 grid gap-x-4 gap-y-1 text-sm sm:grid-cols-[max-content_1fr]">
                      {changes.map((change) => (
                        <div key={change.field} className="contents">
                          <dt className="font-medium">{change.field}</dt>
                          <dd className="break-words text-muted">
                            {change.before ?? '—'} → {change.after ?? '—'}
                          </dd>
                        </div>
                      ))}
                    </dl>
                  )}
                </li>
              )
            })}
          </ul>
          {logs.data.meta.last_page > 1 && (
            <nav aria-label={t('admin.audit.pages')} className="flex items-center justify-between gap-3 border-t border-border px-5 py-3">
              <Button variant="secondary" disabled={page <= 1} onClick={() => setPage((value) => value - 1)}>
                {t('common.previous')}
              </Button>
              <span className="text-sm text-muted">{t('admin.notifications.page', { page, total: logs.data.meta.last_page })}</span>
              <Button variant="secondary" disabled={page >= logs.data.meta.last_page} onClick={() => setPage((value) => value + 1)}>
                {t('common.next')}
              </Button>
            </nav>
          )}
        </Card>
      )}
    </>
  )
}
