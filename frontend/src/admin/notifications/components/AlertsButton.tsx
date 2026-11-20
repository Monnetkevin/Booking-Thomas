import { useCallback, useEffect, useId, useMemo, useRef, useState, type CSSProperties } from 'react'
import { createPortal } from 'react-dom'
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

const GAP = 12
const DESKTOP_WIDTH = 1024

/**
 * Position du panneau à partir du bouton : à droite du menu sur ordinateur, dessous sur
 * tablette et téléphone ; hauteur limitée à ce qui reste de l'écran.
 */
function panelPlacement(button: HTMLElement | null): CSSProperties {
  if (!button) return {}
  const rect = button.getBoundingClientRect()
  if (window.innerWidth >= DESKTOP_WIDTH) {
    return { top: rect.top, left: rect.right + GAP, maxHeight: `calc(100dvh - ${rect.top + GAP}px)` }
  }
  return { top: rect.bottom + 8, left: 16, maxHeight: `calc(100dvh - ${rect.bottom + 8 + GAP}px)` }
}

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
  const [placement, setPlacement] = useState<CSSProperties>({})
  const rootRef = useRef<HTMLDivElement>(null)
  const panelRef = useRef<HTMLDivElement>(null)
  const buttonRef = useRef<HTMLButtonElement>(null)
  const panelId = useId()
  const close = useCallback(() => setOpen(false), [])
  // Un clic dans le panneau (rendu hors du bouton, dans le portail) ne le referme pas.
  const zones = useMemo(() => [rootRef, panelRef], [])
  useDismiss(open, zones, close, buttonRef)

  // Panneau en position fixe (le menu latéral défile et le couperait), rendu à la racine de la
  // page : le menu, collant, crée son propre empilement, et la colonne figée du planning ou les
  // barres d'enregistrement passaient par-dessus. Replacé si la fenêtre change.
  useEffect(() => {
    if (!open) return
    const place = () => setPlacement(panelPlacement(buttonRef.current))
    window.addEventListener('resize', place)
    return () => window.removeEventListener('resize', place)
  }, [open])

  // Rendu en fin de page, le panneau est hors de l'ordre de tabulation du bouton : il reçoit le focus.
  useEffect(() => {
    if (open) panelRef.current?.focus()
  }, [open])

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
        onClick={() => {
          if (!open) setPlacement(panelPlacement(buttonRef.current))
          setOpen(!open)
        }}
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

      {open &&
        createPortal(
        <div
          ref={panelRef}
          id={panelId}
          role="dialog"
          aria-label={t('admin.notifications.title')}
          tabIndex={-1}
          style={placement}
          className="admin-typo fixed z-50 flex outline-none w-[min(22rem,calc(100vw-2rem))] flex-col rounded-[var(--radius-card)] border border-border bg-white p-1.5 font-sans text-ink shadow-xl"
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
          <div className="min-h-0 flex-1 overflow-y-auto">
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
        </div>,
          document.body,
        )}
    </div>
  )
}
