import type { TFunction } from 'i18next'
import type { ApartmentSummary } from './types'

/**
 * Ligne de caractéristiques d'un logement (« 60 m² · 4 voyageurs · 1 chambre »),
 * commune aux cartes de la liste et aux tuiles de l'accueil.
 */
export function apartmentFacts(
  apartment: Pick<ApartmentSummary, 'surface_m2' | 'capacity' | 'bedrooms'>,
  t: TFunction,
  { withBedrooms = true }: { withBedrooms?: boolean } = {},
): string {
  return [
    apartment.surface_m2 ? t('listing.surface', { value: apartment.surface_m2 }) : null,
    t('search.guests', { count: apartment.capacity }),
    withBedrooms ? t('listing.bedrooms', { count: apartment.bedrooms }) : null,
  ]
    .filter(Boolean)
    .join(' · ')
}
