import type { BlockKind } from './planningLayout'

/** Couleurs des séjours, partagées par le planning et l'aperçu du tableau de bord. */
export const blockKindClasses: Record<BlockKind, string> = {
  direct: 'bg-accent text-white',
  manualBooking: 'bg-success text-white',
  pending: 'border-2 border-dashed border-warning bg-warning-soft text-warning',
  ical: 'bg-night-soft text-white',
  closed: 'bg-line text-ink',
}
