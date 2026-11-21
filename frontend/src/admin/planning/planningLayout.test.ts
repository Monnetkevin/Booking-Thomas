import { describe, expect, it } from 'vitest'
import { addDays, blockKind, nightKinds, placeBlock, planningDays } from './planningLayout'
import type { PlanningBlock } from './types'

describe('placement au planning', () => {
  it('compte les jours en traversant les mois', () => {
    expect(addDays('2026-11-30', 2)).toBe('2026-12-02')
    expect(planningDays('2026-12-30', 3)).toEqual(['2026-12-30', '2026-12-31', '2027-01-01'])
  })

  it('place les nuits [arrivée, départ[ et coupe ce qui dépasse', () => {
    expect(placeBlock({ starts_on: '2026-11-22', ends_on: '2026-11-25' }, '2026-11-20', 14)).toEqual({
      start: 2,
      span: 3,
      continuesBefore: false,
      continuesAfter: false,
    })
    expect(placeBlock({ starts_on: '2026-11-18', ends_on: '2026-11-22' }, '2026-11-20', 14)).toEqual({
      start: 0,
      span: 2,
      continuesBefore: true,
      continuesAfter: false,
    })
    expect(placeBlock({ starts_on: '2026-12-02', ends_on: '2026-12-08' }, '2026-11-20', 14)?.continuesAfter).toBe(true)
    // Départ le premier jour affiché : aucune nuit visible.
    expect(placeBlock({ starts_on: '2026-11-15', ends_on: '2026-11-20' }, '2026-11-20', 14)).toBeNull()
  })

  it('choisit la couleur selon l’origine et le statut', () => {
    const base: PlanningBlock = { id: 1, type: 'booking', starts_on: '', ends_on: '', has_conflict: false, redundant_closure: false, needs_cleaning: false, label: null, booking: { reference: 'BT-1', status: 'confirmed', source: 'direct', guests: 2 }, external_url: null }
    expect(blockKind(base)).toBe('direct')
    expect(blockKind({ ...base, booking: { ...base.booking!, source: 'manual' } })).toBe('manualBooking')
    expect(blockKind({ ...base, booking: { ...base.booking!, status: 'pending' } })).toBe('pending')
    expect(blockKind({ ...base, type: 'ical', booking: null })).toBe('ical')
    expect(blockKind({ ...base, type: 'manual', booking: null })).toBe('closed')
  })

  it('donne la nature de chaque nuit d’un logement', () => {
    const block = (starts_on: string, ends_on: string, type: 'booking' | 'ical' | 'manual') => ({
      id: 1, type, starts_on, ends_on, has_conflict: false, redundant_closure: false, needs_cleaning: false, label: null, external_url: null,
      booking: type === 'booking' ? { reference: 'BT-1', status: 'confirmed' as const, source: 'direct' as const, guests: 2 } : null,
    })
    const apartment = { id: 1, name: 'Cocon', blocks: [block('2026-11-20', '2026-11-22', 'booking'), block('2026-11-23', '2026-11-24', 'ical')] }
    expect(nightKinds(apartment, ['2026-11-20', '2026-11-21', '2026-11-22', '2026-11-23'])).toEqual(['direct', 'direct', null, 'ical'])
  })
})
