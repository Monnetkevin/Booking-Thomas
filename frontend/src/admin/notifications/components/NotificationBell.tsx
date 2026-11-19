import { useCallback, useId, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { BellIcon } from '@/components/icons'
import { Spinner } from '@/components/ui/Spinner'
import { useDismiss } from '@/components/ui/useDismiss'
import { badgeLabel } from '../notificationFormat'
import { useMarkAllRead, useNotificationList, useUnreadCount } from '../useNotifications'
import { NotificationItem } from './NotificationItem'
import { useOpenNotification } from './useOpenNotification'

/** Dernières notifications montrées dans le panneau de la cloche. */
const PANEL_SIZE = 6

/**
 * Cloche du back-office : nombre de non lues (rafraîchi toutes les minutes) et panneau
 * des dernières notifications. Échap ou un clic en dehors referme le panneau.
 */
export function NotificationBell() {
  const { t } = useTranslation()
  const [open, setOpen] = useState(false)
  const rootRef = useRef<HTMLDivElement>(null)
  const buttonRef = useRef<HTMLButtonElement>(null)
  const panelId = useId()
  const close = useCallback(() => setOpen(false), [])
  useDismiss(open, rootRef, close, buttonRef)

  const unread = useUnreadCount()
  const latest = useNotificationList(1, false, open)
  const markAllRead = useMarkAllRead()
  const openNotification = useOpenNotification(close)
  const count = unread.data ?? 0
  const badge = badgeLabel(count)

  return (
    <div ref={rootRef} className="relative">
      <button
        ref={buttonRef}
        type="button"
        aria-expanded={open}
        aria-controls={panelId}
        aria-label={count > 0 ? t('admin.notifications.bellUnread', { count }) : t('admin.notifications.bell')}
        onClick={() => setOpen((value) => !value)}
        className="relative flex size-11 items-center justify-center rounded-[10px] text-night-text hover:bg-night-soft hover:text-white"
      >
        <BellIcon />
        {badge && (
          <span
            aria-hidden="true"
            className="absolute top-1.5 right-1 min-w-5 rounded-full bg-accent px-1 text-center text-[11px] leading-5 font-bold text-white"
          >
            {badge}
          </span>
        )}
      </button>

      {open && (
        <div
          id={panelId}
          role="dialog"
          aria-label={t('admin.notifications.title')}
          className="absolute top-12 right-0 z-30 w-[min(24rem,calc(100vw-2rem))] rounded-[var(--radius-card)] border border-border bg-white p-2 text-ink shadow-xl lg:right-auto lg:left-0"
        >
          <div className="flex items-center justify-between gap-2 px-3 py-2">
            <h2 className="text-base font-semibold">{t('admin.notifications.title')}</h2>
            {count > 0 && (
              <button
                type="button"
                className="min-h-11 text-sm font-semibold text-accent-strong hover:underline"
                onClick={() => markAllRead.mutate()}
              >
                {t('admin.notifications.markAllRead')}
              </button>
            )}
          </div>
          <div className="max-h-[60vh] overflow-y-auto">
            {latest.isPending ? (
              <Spinner label={t('common.loading')} />
            ) : latest.isError ? (
              <p className="px-3 py-4 text-sm text-danger">{t('common.error')}</p>
            ) : latest.data.data.length === 0 ? (
              <p className="px-3 py-4 text-sm text-muted">{t('admin.notifications.empty')}</p>
            ) : (
              <ul>
                {latest.data.data.slice(0, PANEL_SIZE).map((notification) => (
                  <li key={notification.id}>
                    <NotificationItem notification={notification} onOpen={openNotification} compact />
                  </li>
                ))}
              </ul>
            )}
          </div>
          <Link
            to="/admin/notifications"
            onClick={close}
            className="mt-1 flex min-h-11 items-center justify-center rounded-xl text-sm font-semibold no-underline hover:bg-sand"
          >
            {t('admin.notifications.seeAll')}
          </Link>
        </div>
      )}
    </div>
  )
}
