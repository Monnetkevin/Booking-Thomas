import { useTranslation } from 'react-i18next'
import { Badge } from '@/components/ui/Badge'
import { formatDateTime } from '@/lib/dates'
import { cn } from '@/lib/cn'
import { relativeTime } from '../notificationFormat'
import type { AppNotification } from '../types'

interface NotificationItemProps {
  notification: AppNotification
  onOpen: (notification: AppNotification) => void
  /** Panneau de la cloche : texte sur deux lignes, sans détails. */
  compact?: boolean
}

/** Une notification : urgence, titre, texte, détails, date ; non lue mise en avant. */
export function NotificationItem({ notification, onOpen, compact = false }: NotificationItemProps) {
  const { t } = useTranslation()
  const when = relativeTime(notification.created_at)
  const urgent = notification.level === 'urgent'

  return (
    <button
      type="button"
      onClick={() => onOpen(notification)}
      className={cn(
        'flex w-full gap-3 rounded-xl px-3 py-3 text-left transition-colors hover:bg-sand',
        !notification.read && 'bg-cream/60',
      )}
    >
      <span
        aria-hidden="true"
        className={cn('mt-2 size-2 shrink-0 rounded-full', notification.read ? 'bg-transparent' : urgent ? 'bg-danger' : 'bg-accent')}
      />
      <span className="min-w-0 flex-1">
        <span className="flex flex-wrap items-center gap-2">
          {urgent && <Badge tone="danger">{t('admin.notifications.urgent')}</Badge>}
          <span className={cn('text-[15px]', notification.read ? 'font-medium' : 'font-bold')}>{notification.title}</span>
          {!notification.read && <span className="sr-only">{t('admin.notifications.unreadLabel')}</span>}
        </span>
        <span className={cn('mt-0.5 block text-sm text-muted', compact && 'line-clamp-2')}>{notification.body}</span>
        {!compact && notification.details.length > 0 && (
          <ul className="mt-2 list-disc space-y-0.5 pl-5 text-sm">
            {notification.details.map((detail) => (
              <li key={detail}>{detail}</li>
            ))}
          </ul>
        )}
        <time dateTime={notification.created_at} title={formatDateTime(notification.created_at)} className="mt-1 block text-xs text-muted">
          {when.key === 'date' ? formatDateTime(notification.created_at) : t(`admin.notifications.when.${when.key}`, 'count' in when ? { count: when.count } : {})}
        </time>
      </span>
    </button>
  )
}
