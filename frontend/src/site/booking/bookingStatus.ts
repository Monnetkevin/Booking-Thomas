import type { BookingStatus } from './types'

export type StatusTone = 'success' | 'warning' | 'danger' | 'info'

/** Ton et clés de texte du bandeau de statut de la page de suivi. */
export function bookingStatusView(status: BookingStatus): { tone: StatusTone; key: `tracking.status.${BookingStatus}` } {
  const tones: Record<BookingStatus, StatusTone> = {
    pending: 'warning',
    confirmed: 'success',
    completed: 'info',
    cancelled: 'danger',
    expired: 'danger',
  }

  return { tone: tones[status], key: `tracking.status.${status}` }
}

/** Le paiement peut encore arriver : la page se met à jour d'elle-même. */
export function isAwaitingPayment(status: BookingStatus): boolean {
  return status === 'pending'
}
