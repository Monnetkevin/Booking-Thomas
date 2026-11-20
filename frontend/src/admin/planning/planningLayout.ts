import type { PlanningBlock } from './types'

const DAY_MS = 86_400_000

function utc(iso: string): number {
  const [year = 1970, month = 1, day = 1] = iso.split('-').map(Number)
  return Date.UTC(year, month - 1, day)
}

/** « 2026-11-30 » + 2 → « 2026-12-02 ». */
export function addDays(iso: string, days: number): string {
  return new Date(utc(iso) + days * DAY_MS).toISOString().slice(0, 10)
}

/** Jours affichés : from, from + 1, … (count jours). */
export function planningDays(from: string, count: number): string[] {
  return Array.from({ length: count }, (_, index) => addDays(from, index))
}

export interface BlockPlacement {
  /** Première colonne occupée (0 = premier jour affiché). */
  start: number
  /** Nombre de nuits visibles. */
  span: number
  /** Le séjour commence avant ou finit après la période affichée. */
  continuesBefore: boolean
  continuesAfter: boolean
}

/** Place les nuits [starts_on, ends_on[ dans la période ; null si elles n'y tombent pas. */
export function placeBlock(block: Pick<PlanningBlock, 'starts_on' | 'ends_on'>, from: string, count: number): BlockPlacement | null {
  const first = Math.round((utc(block.starts_on) - utc(from)) / DAY_MS)
  const end = Math.round((utc(block.ends_on) - utc(from)) / DAY_MS)
  const start = Math.max(0, first)
  const stop = Math.min(count, end)
  if (stop <= start) return null
  return { start, span: stop - start, continuesBefore: first < 0, continuesAfter: end > count }
}

export type BlockKind = 'direct' | 'manualBooking' | 'pending' | 'ical' | 'closed'

/** Couleur d'un bloc : réservation du site, saisie à la main, paiement en cours, plateforme, fermeture. */
export function blockKind(block: PlanningBlock): BlockKind {
  if (block.type === 'ical') return 'ical'
  if (block.type === 'manual') return 'closed'
  if (block.booking?.status === 'pending') return 'pending'
  return block.booking?.source === 'manual' ? 'manualBooking' : 'direct'
}
