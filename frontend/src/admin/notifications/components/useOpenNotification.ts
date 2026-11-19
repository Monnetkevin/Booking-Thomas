import { useNavigate } from 'react-router'
import { notificationLink } from '../notificationFormat'
import type { AppNotification } from '../types'
import { useMarkRead } from '../useNotifications'

/** Ouvrir une notification : la marquer lue, puis aller à la page concernée s'il y en a une. */
export function useOpenNotification(afterOpen?: () => void) {
  const navigate = useNavigate()
  const markRead = useMarkRead()

  return (notification: AppNotification) => {
    if (!notification.read) markRead.mutate(notification.id)
    const link = notificationLink(notification.link)
    afterOpen?.()
    if (link) void navigate(link)
  }
}
