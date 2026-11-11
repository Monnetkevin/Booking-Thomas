import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { ArrowUpRightIcon } from '@/components/icons'
import { RoundBadge } from '@/components/ui/RoundBadge'
import { useLocale } from '@/i18n/useLocale'
import { cn } from '@/lib/cn'
import { apartmentFacts } from '@/site/catalog/apartmentFacts'
import type { ApartmentSummary } from '@/site/catalog/types'
import { Picture } from './Picture'

/** Fonds de secours des tuiles, tant qu'une photo n'est pas prête. */
const fallbackColors = ['bg-[#e8d3b8]', 'bg-[#ebdccb]', 'bg-[#f1d3cb]', 'bg-[#d9cdbf]', 'bg-[#e9d6da]']

interface ApartmentTileProps {
  apartment: ApartmentSummary
  /** Grande tuile (deux colonnes, deux rangées) de la mosaïque. */
  large?: boolean
  /** Rang dans la mosaïque, pour varier le fond de secours. */
  index?: number
}

/** Tuile d'un logement : photo pleine, cartouche avec nom, accroche et capacité ; toute la tuile est cliquable. */
export function ApartmentTile({ apartment, large = false, index = 0 }: ApartmentTileProps) {
  const { t } = useTranslation()
  const { to } = useLocale()

  return (
    <article
      className={cn(
        'group relative isolate flex flex-col justify-end overflow-hidden rounded-[var(--radius-card)]',
        fallbackColors[index % fallbackColors.length],
        large ? 'min-h-[360px] p-5 sm:col-span-2 sm:p-7 lg:row-span-2 lg:min-h-0' : 'min-h-[260px] p-4 sm:p-5 lg:min-h-0',
      )}
    >
      {apartment.cover && (
        <div className="absolute inset-0 -z-10 transition-transform duration-500 group-hover:scale-[1.03]">
          <Picture
            photo={apartment.cover}
            sizes={large ? '(min-width: 1024px) 600px, 100vw' : '(min-width: 1024px) 300px, (min-width: 640px) 50vw, 100vw'}
            priority={large}
            alt=""
          />
        </div>
      )}
      <div className="flex items-end justify-between gap-3 rounded-[18px] bg-[rgba(255,253,249,0.92)] px-4 py-3.5 sm:px-5">
        <div className="flex min-w-0 flex-col gap-0.5">
          <h3 className={large ? 'text-[22px]' : 'text-[17px]'}>
            {/* Lien étiré sur toute la tuile. */}
            <Link to={to(`/logements/${apartment.slug}`)} className="text-ink no-underline after:absolute after:inset-0 hover:text-ink">
              {apartment.name}
            </Link>
          </h3>
          {apartment.tagline && <p className="text-[13px] text-muted">{apartment.tagline}</p>}
          <p className="text-[13px] text-muted">{apartmentFacts(apartment, t, { withBedrooms: false })}</p>
        </div>
        <RoundBadge tone="dark">
          <ArrowUpRightIcon aria-hidden="true" />
        </RoundBadge>
      </div>
    </article>
  )
}
