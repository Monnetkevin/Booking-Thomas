import { addDays, blockKind, nightKinds, planningDays } from './planningLayout'
import type { Planning } from './types'

export interface PlanningFigures {
  /** Nuits vendues (site, saisies à la main, plateformes). */
  soldNights: number
  /** Nuits ouvertes à la vente (hors fermetures). */
  openNights: number
  /** Nuits vendues / ouvertes, en %, ou null sans nuit ouverte. */
  occupancy: number | null
  arrivals: number
  directArrivals: number
  /** Nuits libres, dont nuits de week-end (vendredi et samedi). */
  freeNights: number
  freeWeekendNights: number
}

const SOLD = new Set(['direct', 'manualBooking', 'ical'])

function isWeekendNight(day: string): boolean {
  const weekday = new Date(`${day}T00:00:00Z`).getUTCDay()
  return weekday === 5 || weekday === 6
}

/** Chiffres de la période affichée, calculés sur les nuits [from, from + days[. */
export function planningFigures(planning: Planning, days: number): PlanningFigures {
  const nights = planningDays(planning.from, days)
  const end = addDays(planning.from, days)
  const figures: PlanningFigures = { soldNights: 0, openNights: 0, occupancy: null, arrivals: 0, directArrivals: 0, freeNights: 0, freeWeekendNights: 0 }

  for (const apartment of planning.apartments) {
    nightKinds(apartment, nights).forEach((kind, index) => {
      if (kind === 'closed') return
      figures.openNights += 1
      if (kind && SOLD.has(kind)) figures.soldNights += 1
      if (kind === null) {
        figures.freeNights += 1
        if (isWeekendNight(nights[index] ?? '')) figures.freeWeekendNights += 1
      }
    })
    for (const block of apartment.blocks) {
      const kind = blockKind(block)
      if (!SOLD.has(kind) || block.starts_on < planning.from || block.starts_on >= end) continue
      figures.arrivals += 1
      if (kind !== 'ical') figures.directArrivals += 1
    }
  }

  figures.occupancy = figures.openNights === 0 ? null : Math.round((figures.soldNights / figures.openNights) * 1000) / 10
  return figures
}

/** « Camille Martin » → « C. Martin » ; un seul mot reste tel quel. */
export function shortGuestName(name: string): string {
  const words = name.trim().split(/\s+/)
  if (words.length < 2) return name.trim()
  return `${words[0]?.charAt(0).toLocaleUpperCase('fr-FR')}. ${words.slice(1).join(' ')}`
}
