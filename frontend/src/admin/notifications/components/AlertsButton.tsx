import { useCallback, useId, useRef, useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { BellIcon } from '@/components/icons'
import { Spinner } from '@/components/ui/Spinner'
import { cn } from '@/lib/cn'
import { useDismiss } from '@/components/ui/useDismiss'
import { badgeLabel } from '../notificationFormat'
import { useMarkAllRead, useNotificationList, useUnreadCount } from '../useNotifications'
import { NotificationItem } from './NotificationItem'
import { useOpenNotification } from './useOpenNotification'

/** Dernières notifications montrées dans le panneau de la cloche. */
const PANEL_SIZE = 6

interface AlertsButtonProps {
  /** Résumé des situations à traiter (« 1 conflit · 1 erreur iCal »), null s'il n'y en a pas. */
  summary: string | null
  /** Menu réduit : icône seule, libellé en infobulle. */
  collapsed?: boolean
}

/**
 * Bouton « Alertes » du menu latéral : nombre de notifications non lues (relu chaque
 * minute), résumé des situations à traiter, et panneau des dernières notifications.
 * Échap ou un clic en dehors referme le panneau.
 */
export function AlertsButton({ summary, collapsed = false }: AlertsButtonProps) {
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
  const alerting = count > 0 || summary !== null
  const label = count > 0 ? t('admin.notifications.bellUnread', { count }) : t('admin.alerts.title')

  return (
    <div ref={rootRef} className="relative">
      <button
        ref={buttonRef}
        type="button"
        aria-expanded={open}
        aria-controls={panelId}
        aria-label={collapsed ? label : undefined}
        title={collapsed ? label : undefined}
        onClick={() => setOpen((value) => !value)}
        className={cn(
          'flex w-full items-center gap-3 rounded-2xl border text-left transition-colors',
          collapsed ? 'justify-center border-transparent p-1.5' : 'px-3 py-2.5',
          alerting && !collapsed ? 'border-danger/25 bg-danger-soft text-danger hover:bg-danger-soft/70' : !collapsed && 'border-border bg-white hover:bg-sand',
        )}
      >
        <span className={cn('relative flex size-10 shrink-0 items-center justify-center rounded-xl bg-white', alerting ? 'text-danger' : 'text-ink', collapsed && 'border border-border')}>
          <BellIcon />
          {badge && (
            <span
              aria-hidden="true"
              className="absolute -top-1.5 -right-1.5 min-w-5 rounded-full border-2 border-white bg-danger px-1 text-center text-[11px] leading-4 font-bold text-white"
            >
              {badge}
            </span>
          )}
        </span>
        {!collapsed && (
          <span className="flex min-w-0 flex-col leading-tight">
            <strong className="text-[15px]">{t('admin.alerts.title')}</strong>
            <span className={cn('truncate text-xs', alerting ? 'text-danger' : 'text-muted')}>
              {summary ?? (count > 0 ? t('admin.alerts.unread', { count }) : t('admin.alerts.none'))}
            </span>
          </span>
        )}
      </button>

      {open && (
        <div
          id={panelId}
          role="dialog"
          aria-label={t('admin.notifications.title')}
          className="absolute top-full left-0 z-40 mt-2 w-[min(22rem,calc(100vw-2rem))] rounded-[var(--radius-card)] border border-border bg-white p-1.5 font-sans text-ink shadow-xl lg:top-0 lg:left-full lg:mt-0 lg:ml-3"
        >
          <div className="flex items-center justify-between gap-2 px-2.5 py-1">
            <h2 className="font-sans text-sm font-bold tracking-normal">{t('admin.notifications.title')}</h2>
            {count > 0 && (
              <button
                type="button"
                className="min-h-11 text-xs font-semibold text-accent-strong hover:underline"
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
            className="mt-1 flex min-h-11 items-center justify-center rounded-xl text-[13px] font-semibold no-underline hover:bg-sand"
          >
            {t('admin.notifications.seeAll')}
          </Link>
        </div>
      )}
    </div>
  )
}
