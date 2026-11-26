import { useTranslation } from 'react-i18next'
import { Alert } from '@/components/ui/Alert'
import { formatDateTime } from '@/lib/dates'
import type { IcalFeed } from '../types'

/** Dernière lecture, dernier import réussi et, en cas de panne, l'erreur. */
export function FeedState({ feed }: { feed: IcalFeed }) {
  const { t } = useTranslation()

  if (!feed.import_url || !feed.last_synced_at) return null

  return (
    <div className="mb-5 space-y-2 text-sm">
      <p className="text-muted">
        {t('admin.sync.lastSync', { date: formatDateTime(feed.last_synced_at) })} ·{' '}
        {feed.last_succeeded_at
          ? t('admin.sync.lastSuccess', { date: formatDateTime(feed.last_succeeded_at) })
          : t('admin.sync.neverSucceeded')}
      </p>
      {feed.last_status === 'failed' && (
        <Alert tone="danger">
          {t('admin.sync.failures', { count: feed.consecutive_failures, error: feed.last_error ?? '' })}
        </Alert>
      )}
    </div>
  )
}
