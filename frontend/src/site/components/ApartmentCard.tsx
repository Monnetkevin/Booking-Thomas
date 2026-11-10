import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { Badge } from '@/components/ui/Badge'
import { buttonClasses } from '@/components/ui/buttonClasses'
import { useLocale } from '@/i18n/useLocale'
import { formatEuros } from '@/lib/money'
import type { ApartmentSummary, SearchResult } from '@/site/catalog/types'
import { Picture } from './Picture'

interface ApartmentCardProps {
  apartment: ApartmentSummary | SearchResult
  /** Paramètres de recherche transmis à la fiche (dates, voyageurs). */
  search?: string
  headingLevel?: 'h2' | 'h3'
}

function isSearchResult(apartment: ApartmentSummary | SearchResult): apartment is SearchResult {
  return 'available' in apartment
}

/** Carte d'un logement : photo, accroche, capacité, quelques équipements et prix. */
export function ApartmentCard({ apartment, search = '', headingLevel: Heading = 'h3' }: ApartmentCardProps) {
  const { t } = useTranslation()
  const { locale, to } = useLocale()
  const numberLocale = locale === 'fr' ? 'fr-FR' : 'en-GB'
  const result = isSearchResult(apartment) ? apartment : null
  const href = to(`/logements/${apartment.slug}`) + (search ? `?${search}` : '')

  const meta = [
    apartment.surface_m2 ? t('listing.surface', { value: apartment.surface_m2 }) : null,
    t('search.guests', { count: apartment.capacity }),
    t('listing.bedrooms', { count: apartment.bedrooms }),
  ].filter(Boolean)

  return (
    <article className="flex flex-col overflow-hidden rounded-[20px] border border-border bg-white">
      <div className="relative aspect-[3/2] bg-cream">
        {apartment.cover && <Picture photo={apartment.cover} sizes="(min-width: 1024px) 380px, (min-width: 640px) 50vw, 100vw" alt="" />}
        {result && (
          <span className="absolute top-3.5 left-3.5">
            <Badge tone={result.available ? 'success' : 'danger'}>
              {result.available ? t('listing.available') : t('listing.unavailable')}
            </Badge>
          </span>
        )}
      </div>
      <div className="flex flex-1 flex-col gap-2.5 p-5">
        <Heading className="text-[22px] leading-tight font-semibold">{apartment.name}</Heading>
        {apartment.tagline && <p className="text-[15px] font-semibold text-accent-strong">{apartment.tagline}</p>}
        <p className="text-sm text-muted">{meta.join(' · ')}</p>
        {apartment.amenities.length > 0 && (
          <ul className="flex flex-wrap gap-1.5" aria-label={t('detail.amenities')}>
            {apartment.amenities.slice(0, 3).map((amenity) => (
              <li key={amenity.key} className="rounded-full border border-border px-2.5 py-1 text-xs text-muted">
                {amenity.label}
              </li>
            ))}
          </ul>
        )}
        <div className="mt-auto flex flex-wrap items-center justify-between gap-3 pt-3">
          <p className="text-[15px]">
            {result?.quote ? (
              <strong>{t('listing.total', { price: formatEuros(result.quote.total, numberLocale), count: result.quote.nights })}</strong>
            ) : apartment.price_from !== null ? (
              <>
                <span className="text-muted">{t('listing.from', { price: '' })}</span>
                <strong>{formatEuros(apartment.price_from, numberLocale)}</strong>{' '}
                <span className="text-muted">{t('listing.perNight')}</span>
              </>
            ) : null}
          </p>
          <Link
            to={result && !result.available ? to(`/logements/${apartment.slug}`) : href}
            aria-label={t('listing.viewLabel', { name: apartment.name })}
            className={buttonClasses(result && !result.available ? 'secondary' : 'primary')}
          >
            {result && !result.available ? t('listing.otherDates') : t('listing.view')}
          </Link>
        </div>
        {result && !result.available && result.unavailable_reason && (
          <p className="text-sm text-muted">{result.unavailable_reason}</p>
        )}
      </div>
    </article>
  )
}
