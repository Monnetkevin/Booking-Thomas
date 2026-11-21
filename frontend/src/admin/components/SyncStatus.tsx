import { useTranslation } from 'react-i18next'
import { relativeTime } from '@/admin/notifications/notificationFormat'
import { formatDateTime } from '@/lib/dates'
import { cn } from '@/lib/cn'

/** Dernière lecture réussie des calendriers, en bas du menu latéral. */
export function SyncStatus({ lastSyncAt, failing }: { lastSyncAt: string | null; failing: boolean }) {
  const { t } = useTranslation()
  const when = lastSyncAt ? relativeTime(lastSyncAt) : null
  const label = !lastSyncAt || !when
    ? t('admin.syncStatus.never')
    : when.key === 'date'
      ? formatDateTime(lastSyncAt)
      : t(`admin.notifications.when.${when.key}`, 'count' in when ? { count: when.count } : {})

  return (
    <div className="flex items-center gap-2.5 rounded-xl bg-sand px-3.5 py-3 text-[13px] text-muted">
      <span aria-hidden="true" className={cn('size-2.5 shrink-0 rounded-full', failing ? 'bg-danger' : lastSyncAt ? 'bg-success' : 'bg-field')} />
      <span>
        {t('admin.syncStatus.label')}
        <br />
        <strong className="text-ink">{label}</strong>
      </span>
    </div>
  )
}
