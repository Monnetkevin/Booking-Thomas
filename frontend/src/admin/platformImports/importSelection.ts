import type { ImportPreview, PreviewRow } from './types'

/** Choix de l'hôte pour chaque annonce du fichier (null : non rattachée). */
export type ListingMapping = Record<string, number | null>

/** État d'une ligne de l'aperçu selon le rattachement choisi. */
export type RowState = 'new' | 'update' | 'unlinked' | 'removed'

export interface ImportSelection {
  /** Annonces rattachées à aucun logement. */
  unmapped: number
  /** Lignes qui seront importées (annonce rattachée). */
  importable: PreviewRow[]
  /** Versements déjà importés d'une annonce non rattachée : le serveur les retire à l'import. */
  detachedRemoved: number
}

export function isLinked(mapping: ListingMapping, listing: string): boolean {
  return mapping[listing] !== null && mapping[listing] !== undefined
}

export function rowState(row: PreviewRow, mapping: ListingMapping): RowState {
  if (!isLinked(mapping, row.listing)) return row.already_imported ? 'removed' : 'unlinked'
  return row.already_imported ? 'update' : 'new'
}

/** Ce que l'import fera avec le rattachement choisi (même règle que le serveur). */
export function importSelection(preview: ImportPreview | undefined, mapping: ListingMapping): ImportSelection {
  if (!preview) return { unmapped: 0, importable: [], detachedRemoved: 0 }
  return {
    unmapped: preview.listings.filter((entry) => !isLinked(mapping, entry.listing)).length,
    importable: preview.rows.filter((row) => isLinked(mapping, row.listing)),
    detachedRemoved: preview.rows.filter((row) => rowState(row, mapping) === 'removed').length,
  }
}

/** Rattachement initial : celui retenu par le serveur pour chaque annonce. */
export function initialMapping(preview: ImportPreview): ListingMapping {
  return Object.fromEntries(preview.listings.map((entry) => [entry.listing, entry.apartment_id]))
}
