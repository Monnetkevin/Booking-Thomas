import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { SyncIcon } from '@/components/icons'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { ConfirmDialog } from '@/components/ui/ConfirmDialog'
import { ApiError } from '@/lib/api'
import type { IcalFeed } from '../types'
import { useSyncNow } from '../useSync'
import { SyncLogList } from './SyncLogList'
import { SyncOutcome } from './SyncOutcome'

const resultTone = { success: 'success', unchanged: 'info', failed: 'danger' } as const

/** « Synchroniser maintenant », la confirmation d'un calendrier vide, le bilan et le journal. */
export function SyncActions({ feed }: { feed: IcalFeed }) {
  const { t } = useTranslation()
  const [showLog, setShowLog] = useState(false)
  const [confirmingEmpty, setConfirmingEmpty] = useState(false)
  const sync = useSyncNow()

  return (
    <div className="mt-6 space-y-4 border-t border-border pt-5">
      {feed.is_active && feed.empty_calendar_refused && (
        <p className="text-sm text-muted">{t('admin.sync.acceptEmpty.hint', { provider: feed.provider_label })}</p>
      )}
      <div className="flex flex-wrap gap-2">
        <Button onClick={() => sync.mutate({ feedId: feed.id })} loading={sync.isPending} disabled={!feed.is_active}>
          <SyncIcon />
          {t('admin.sync.syncNow')}
        </Button>
        {feed.is_active && feed.empty_calendar_refused && (
          <Button variant="ghost" onClick={() => setConfirmingEmpty(true)}>
            {t('admin.sync.acceptEmpty.action')}
          </Button>
        )}
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
      <ConfirmDialog
        open={confirmingEmpty}
        title={t('admin.sync.acceptEmpty.confirmTitle')}
        confirmLabel={t('admin.sync.acceptEmpty.confirmAction')}
        cancelLabel={t('admin.common.cancel')}
        tone="danger"
        pending={sync.isPending}
        onCancel={() => setConfirmingEmpty(false)}
        onConfirm={() => sync.mutate({ feedId: feed.id, acceptEmpty: true }, { onSettled: () => setConfirmingEmpty(false) })}
      >
        {t('admin.sync.acceptEmpty.confirm', { provider: feed.provider_label })}
      </ConfirmDialog>
    </div>
  )
}
