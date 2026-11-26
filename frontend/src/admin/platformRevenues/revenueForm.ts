import { centsToInput, parseEuros } from '@/lib/money'
import { REVENUE_PLATFORMS, type RevenueEntry, type RevenuePlatform, type RevenueSheet } from './types'

/** Valeurs tapées, par case « logement|plateforme ». */
export type RevenueDrafts = Record<string, string>

export const cellKey = (apartmentId: number, platform: RevenuePlatform) => `${apartmentId}|${platform}`

/** Mois « AAAA-MM » décalé de n mois. */
export function shiftMonth(month: string, by: number): string {
  const [year = 1970, number = 1] = month.split('-').map(Number)
  const date = new Date(Date.UTC(year, number - 1 + by, 1))
  return `${date.getUTCFullYear()}-${String(date.getUTCMonth() + 1).padStart(2, '0')}`
}

/** Premier mois accepté par l'API. */
export const FIRST_MONTH = '2020-01'

export function currentMonth(today: Date = new Date()): string {
  return `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`
}

/** Mois ouvert par défaut : le précédent, dont la plateforme a publié le rapport. */
export function defaultMonth(today: Date = new Date()): string {
  return shiftMonth(currentMonth(today), -1)
}

export function initialDrafts(sheet: RevenueSheet): RevenueDrafts {
  const drafts: RevenueDrafts = {}
  for (const apartment of sheet.apartments) {
    for (const platform of REVENUE_PLATFORMS) {
      drafts[cellKey(apartment.id, platform)] = centsToInput(apartment.platforms[platform].net_amount)
    }
  }
  return drafts
}

export interface RevenueSummary {
  /** Cases dont le montant diffère de la feuille chargée : seules envoyées, pour ne pas écraser la saisie d'un autre. */
  entries: RevenueEntry[]
  /** Cases dont la saisie n'est pas un montant. */
  invalid: string[]
  /** Cases avec des nuits et aucun montant. */
  missing: number
  totals: Record<RevenuePlatform, number>
  /** Net par nuit de chaque logement, null sans nuit ou sans montant. */
  perNight: Record<number, number | null>
}

/**
 * Lit la saisie, une case par logement et plateforme (un montant sans nuit ce mois-là, comme
 * des frais d'annulation, se saisit aussi) : montants en centimes (vide → null, efface), erreurs, cases à saisir,
 * totaux et net par nuit, recalculés à chaque frappe.
 */
export function summarize(sheet: RevenueSheet, drafts: RevenueDrafts): RevenueSummary {
  const summary: RevenueSummary = { entries: [], invalid: [], missing: 0, totals: { airbnb: 0, booking: 0 }, perNight: {} }
  for (const apartment of sheet.apartments) {
    let nights = 0
    let amount = 0
    for (const platform of REVENUE_PLATFORMS) {
      const key = cellKey(apartment.id, platform)
      const text = (drafts[key] ?? '').trim()
      const cents = text === '' ? null : parseEuros(text)
      if (text !== '' && cents === null) {
        summary.invalid.push(key)
        continue
      }
      if (cents !== apartment.platforms[platform].net_amount) {
        summary.entries.push({ apartment_id: apartment.id, platform, net_amount: cents })
      }
      const cellNights = apartment.platforms[platform].nights
      if (cents === null) {
        if (cellNights > 0) summary.missing++
        continue
      }
      summary.totals[platform] += cents
      if (cellNights > 0) {
        nights += cellNights
        amount += cents
      }
    }
    summary.perNight[apartment.id] = nights > 0 ? Math.round(amount / nights) : null
  }
  return summary
}
