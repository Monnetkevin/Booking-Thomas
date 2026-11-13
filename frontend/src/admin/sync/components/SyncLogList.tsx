import { useTranslation } from 'react-i18next'
import { ErrorState } from '@/components/feedback/ErrorState'
import { Badge } from '@/components/ui/Badge'
import { Spinner } from '@/components/ui/Spinner'
import { formatDateTime } from '@/lib/dates'
import type { IcalSyncStatus } from '../types'
import { useIcalLogs } from '../useSync'
import { SyncOutcome } from './SyncOutcome'

const statusTone = { success: 'success', unchanged: 'neutral', failed: 'danger' } as const satisfies Record<IcalSyncStatus, string>

/** Journal d'un calendrier : les 20 dernières lectures, la plus récente en tête. */
export function SyncLogList({ feedId }: { feedId: number }) {
  const { t } = useTranslation()
  const logs = useIcalLogs(feedId, true)

  if (logs.isPending) return <Spinner label={t('common.loading')} />
  if (logs.isError) return <ErrorState onRetry={() => void logs.refetch()} />
  if (logs.data.length === 0) return <p className="py-3 text-sm text-muted">{t('admin.sync.log.empty')}</p>

  return (
    <div className="overflow-x-auto">
      <table className="w-full text-left text-sm">
        <caption className="sr-only">{t('admin.sync.log.title')}</caption>
        <thead className="text-muted">
          <tr>
            <th scope="col" className="py-2 pr-3 font-semibold">{t('admin.sync.log.date')}</th>
            <th scope="col" className="py-2 pr-3 font-semibold">{t('admin.sync.log.trigger')}</th>
            <th scope="col" className="py-2 font-semibold">{t('admin.sync.log.result')}</th>
          </tr>
        </thead>
        <tbody>
          {logs.data.map((log) => (
            <tr key={log.id} className="border-t border-border align-top">
              <td className="py-2 pr-3 whitespace-nowrap">{formatDateTime(log.synced_at)}</td>
              <td className="py-2 pr-3">{t(`admin.sync.log.${log.trigger}`)}</td>
              <td className="py-2">
                <span className="mr-2">
                  <Badge tone={statusTone[log.status]}>{t(`admin.sync.log.status.${log.status}`)}</Badge>
                </span>
                <SyncOutcome log={log} />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
