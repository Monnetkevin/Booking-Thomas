import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { SyncIcon } from '@/components/icons'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { ApiError } from '@/lib/api'
import type { IcalFeed } from '../types'
import { useSyncNow } from '../useSync'
import { SyncLogList } from './SyncLogList'
import { SyncOutcome } from './SyncOutcome'

const resultTone = { success: 'success', unchanged: 'info', failed: 'danger' } as const

/** « Synchroniser maintenant », son bilan, et le journal du calendrier. */
export function SyncActions({ feed }: { feed: IcalFeed }) {
  const { t } = useTranslation()
  const [showLog, setShowLog] = useState(false)
  const sync = useSyncNow()

  return (
    <div className="mt-6 space-y-4 border-t border-border pt-5">
      <div className="flex flex-wrap gap-2">
        <Button onClick={() => sync.mutate(feed.id)} loading={sync.isPending} disabled={!feed.is_active}>
          <SyncIcon />
          {t('admin.sync.syncNow')}
        </Button>
        <Button variant="ghost" aria-expanded={showLog} onClick={() => setShowLog((open) => !open)}>
          {showLog ? t('admin.sync.log.hide') : t('admin.sync.log.show')}
        </Button>
      </div>
      {sync.data && (
        <Alert tone={resultTone[sync.data.status]}>
          {sync.data.status === 'success' && `${t('admin.sync.result.success')} `}
          <SyncOutcome log={sync.data} />
        </Alert>
      )}
      {sync.isError && (
        <Alert tone="danger">{(sync.error instanceof ApiError && sync.error.serverMessage) || t('common.error')}</Alert>
      )}
      {showLog && <SyncLogList feedId={feed.id} />}
    </div>
  )
}
