import type { TFunction } from 'i18next'
import { describe, expect, it } from 'vitest'
import { apartmentFacts } from './apartmentFacts'

// Traduction factice : renvoie la clé et ses valeurs, pour vérifier l'assemblage.
const t = ((key: string, options?: { value?: number; count?: number }) => `${key}:${options?.value ?? options?.count}`) as unknown as TFunction

describe('caractéristiques d’un logement', () => {
  it('assemble surface, voyageurs et chambres', () => {
    expect(apartmentFacts({ surface_m2: 60, capacity: 4, bedrooms: 1 }, t)).toBe(
      'listing.surface:60 · search.guests:4 · listing.bedrooms:1',
    )
  })

  it('omet la surface inconnue et, sur demande, les chambres', () => {
    expect(apartmentFacts({ surface_m2: null, capacity: 2, bedrooms: 1 }, t, { withBedrooms: false })).toBe('search.guests:2')
  })
})
