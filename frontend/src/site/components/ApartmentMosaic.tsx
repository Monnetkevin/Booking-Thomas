import { ErrorState } from '@/components/feedback/ErrorState'
import { cn } from '@/lib/cn'
import type { ApartmentSummary } from '@/site/catalog/types'
import { ApartmentTile } from './ApartmentTile'

/** Nombre de logements montrés : une grande tuile et quatre petites. */
export const MOSAIC_SIZE = 5

interface ApartmentMosaicProps {
  apartments: ApartmentSummary[]
  loading: boolean
  error: boolean
  onRetry: () => void
}

/** Mosaïque des logements : le premier en grand, les suivants en petites tuiles. */
export function ApartmentMosaic({ apartments, loading, error, onRetry }: ApartmentMosaicProps) {
  if (error) return <ErrorState onRetry={onRetry} />

  return (
    <div className="grid gap-4 sm:grid-cols-2 lg:auto-rows-[300px] lg:grid-cols-4" aria-busy={loading}>
      {loading
        ? Array.from({ length: MOSAIC_SIZE }, (_, i) => (
            <div
              key={i}
              className={cn(
                'animate-pulse rounded-[var(--radius-card)] bg-cream',
                i === 0 ? 'min-h-[360px] sm:col-span-2 lg:row-span-2' : 'min-h-[260px]',
              )}
            />
          ))
        : apartments
            .slice(0, MOSAIC_SIZE)
            .map((apartment, index) => <ApartmentTile key={apartment.slug} apartment={apartment} large={index === 0} index={index} />)}
    </div>
  )
}
