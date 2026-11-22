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

/** Un champ s'affiche quand la plateforme a des nuits, ou un montant déjà saisi. */
export function isEditable(sheet: RevenueSheet, apartmentId: number, platform: RevenuePlatform): boolean {
  const cell = sheet.apartments.find((apartment) => apartment.id === apartmentId)?.platforms[platform]
  return cell !== undefined && (cell.nights > 0 || cell.net_amount !== null)
}

export interface RevenueSummary {
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
 * Lit la saisie : montants en centimes (vide → null, efface), erreurs, cases à saisir,
 * totaux et net par nuit, recalculés à chaque frappe.
 */
export function summarize(sheet: RevenueSheet, drafts: RevenueDrafts): RevenueSummary {
  const summary: RevenueSummary = { entries: [], invalid: [], missing: 0, totals: { airbnb: 0, booking: 0 }, perNight: {} }
  for (const apartment of sheet.apartments) {
    let nights = 0
    let amount = 0
    for (const platform of REVENUE_PLATFORMS) {
      if (!isEditable(sheet, apartment.id, platform)) continue
      const key = cellKey(apartment.id, platform)
      const text = (drafts[key] ?? '').trim()
      const cents = text === '' ? null : parseEuros(text)
      if (text !== '' && cents === null) {
        summary.invalid.push(key)
        continue
      }
      summary.entries.push({ apartment_id: apartment.id, platform, net_amount: cents })
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
