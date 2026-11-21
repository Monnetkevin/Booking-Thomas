import { describe, expect, it } from 'vitest'
import { importSelection, initialMapping, isLinked, rowState } from './importSelection'
import type { ImportPreview, PreviewRow } from './types'

const row = (overrides: Partial<PreviewRow>): PreviewRow => ({
  line: 2,
  entry_key: 'k',
  confirmation_code: 'HM1',
  listing: 'Studio',
  guest: null,
  arrival: '2026-11-04',
  departure: '2026-11-07',
  gross_amount: null,
  fees: null,
  net_amount: 10000,
  already_imported: false,
  ...overrides,
})

const preview: ImportPreview = {
  rows: [
    row({ entry_key: 'a', listing: 'Studio', already_imported: false }),
    row({ entry_key: 'b', listing: 'Studio', already_imported: true }),
    row({ entry_key: 'c', listing: 'Loft', already_imported: true }),
    row({ entry_key: 'd', listing: 'Loft', already_imported: false }),
    row({ entry_key: 'e', listing: 'Grenier', already_imported: false }),
  ],
  skipped: [],
  cancelled: 0,
  listings: [
    { listing: 'Studio', apartment_id: 1 },
    { listing: 'Loft', apartment_id: 2 },
    { listing: 'Grenier', apartment_id: null },
  ],
}

describe('aperçu de l’import des revenus', () => {
  it('reprend le rattachement retenu par le serveur', () => {
    expect(initialMapping(preview)).toEqual({ Studio: 1, Loft: 2, Grenier: null })
  })

  it('considère une annonce absente ou mise à null comme non rattachée', () => {
    expect(isLinked({ Studio: 1 }, 'Studio')).toBe(true)
    expect(isLinked({ Studio: null }, 'Studio')).toBe(false)
    expect(isLinked({}, 'Studio')).toBe(false)
  })

  it('donne l’état de chaque ligne : nouvelle, mise à jour, non rattachée, retirée', () => {
    const mapping = { Studio: 1, Loft: null }
    expect(rowState(row({ listing: 'Studio' }), mapping)).toBe('new')
    expect(rowState(row({ listing: 'Studio', already_imported: true }), mapping)).toBe('update')
    expect(rowState(row({ listing: 'Loft' }), mapping)).toBe('unlinked')
    expect(rowState(row({ listing: 'Loft', already_imported: true }), mapping)).toBe('removed')
  })

  it('compte les versements déjà importés qui seront retirés quand l’hôte détache une annonce', () => {
    expect(importSelection(preview, initialMapping(preview)).detachedRemoved).toBe(0)

    const detached = importSelection(preview, { Studio: 1, Loft: null, Grenier: null })
    expect(detached.detachedRemoved).toBe(1)
    expect(detached.unmapped).toBe(2)
    expect(detached.importable.map((entry) => entry.entry_key)).toEqual(['a', 'b'])
  })

  it('ne compte rien sans aperçu', () => {
    expect(importSelection(undefined, {})).toEqual({ unmapped: 0, importable: [], detachedRemoved: 0 })
  })
})
