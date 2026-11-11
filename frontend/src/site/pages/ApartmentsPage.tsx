import { useMemo } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useSearchParams } from 'react-router'
import { ErrorState } from '@/components/feedback/ErrorState'
import { buttonClasses } from '@/components/ui/buttonClasses'
import { Container } from '@/components/ui/Container'
import { FilterChips } from '@/components/ui/FilterChips'
import { useLocale } from '@/i18n/useLocale'
import { searchFromParams } from '@/site/catalog/searchParams'
import type { ApartmentSummary } from '@/site/catalog/types'
import { useApartments, useSearch } from '@/site/catalog/useCatalog'
import { ApartmentCard } from '@/site/components/ApartmentCard'
import { ApartmentCardSkeleton } from '@/site/components/ApartmentCardSkeleton'
import { SearchForm } from '@/site/components/SearchForm'
import { Seo } from '@/site/components/Seo'

/** Équipements qui distinguent les logements (présents dans certains, pas dans tous), 6 au plus. */
function distinctiveAmenities(apartments: ApartmentSummary[]) {
  const counts = new Map<string, { label: string; count: number }>()
  for (const apartment of apartments) {
    for (const amenity of apartment.amenities) {
      const entry = counts.get(amenity.key) ?? { label: amenity.label, count: 0 }
      counts.set(amenity.key, { ...entry, count: entry.count + 1 })
    }
  }
  return [...counts.entries()]
    .filter(([, entry]) => entry.count < apartments.length)
    .sort((a, b) => b[1].count - a[1].count)
    .slice(0, 6)
    .map(([key, entry]) => ({ value: key, label: entry.label }))
}

export function ApartmentsPage() {
  const { t } = useTranslation()
  const { to } = useLocale()
  const [params, setParams] = useSearchParams()
  const criteria = searchFromParams(params)
  const amenity = params.get('equipement') ?? 'all'

  const all = useApartments()
  const search = useSearch(criteria)
  const source = criteria ? search : all
  const items = useMemo(() => source.data ?? [], [source.data])
  const filters = useMemo(() => distinctiveAmenities(items), [items])
  const visible = amenity === 'all' ? items : items.filter((a) => a.amenities.some((x) => x.key === amenity))
  const availableCount = criteria && search.data ? search.data.filter((r) => r.available).length : null

  const setAmenity = (value: string) => {
    const next = new URLSearchParams(params)
    if (value === 'all') next.delete('equipement')
    else next.set('equipement', value)
    setParams(next, { replace: true })
  }

  return (
    <Container className="flex flex-col gap-7 py-10 sm:py-12">
      <Seo title={t('listing.title')} description={t('listing.metaDescription')} path="/logements" />
      <header className="flex flex-col gap-2">
        <h1 className="text-4xl leading-[1.1] font-semibold sm:text-[44px]">{t('listing.title')}</h1>
        <p className="max-w-[700px] text-[17px] leading-relaxed text-muted">{t('listing.intro')}</p>
      </header>

      <SearchForm key={params.toString()} initial={criteria} layout="pill" />

      <div className="flex flex-wrap items-center justify-between gap-4">
        {filters.length > 0 ? (
          <FilterChips
            label={t('listing.filtersLabel')}
            value={amenity}
            onChange={setAmenity}
            options={[{ value: 'all', label: t('listing.allFilter') }, ...filters]}
          />
        ) : (
          <span />
        )}
        <p className="flex items-center gap-3 text-sm text-muted" aria-live="polite">
          {source.data &&
            (availableCount !== null
              ? t('listing.results', { count: availableCount, total: items.length })
              : t('listing.count', { count: visible.length }))}
          {criteria && (
            <Link to={to('/logements')} className="font-semibold">
              {t('listing.clearSearch')}
            </Link>
          )}
        </p>
      </div>

      {source.isError ? (
        <ErrorState onRetry={() => void source.refetch()} />
      ) : (
        <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" aria-busy={source.isPending}>
          {source.isPending
            ? Array.from({ length: 3 }, (_, i) => (
                <li key={i}>
                  <ApartmentCardSkeleton />
                </li>
              ))
            : visible.map((apartment) => (
                <li key={apartment.slug} className="flex">
                  <ApartmentCard apartment={apartment} search={criteria ? params.toString() : ''} headingLevel="h2" />
                </li>
              ))}
          {!source.isPending && (
            <li className="flex">
              <div className="flex w-full flex-col justify-center gap-3 rounded-[28px] border border-dashed border-[#c9b8a6] bg-cream p-7">
                <h2 className="text-[22px] font-semibold">{t('listing.questionTitle')}</h2>
                <p className="text-[15px] leading-relaxed text-muted">{t('listing.questionText')}</p>
                <Link to={to('/contact')} className={buttonClasses('secondary', 'md', 'self-start')}>
                  {t('listing.contactCta')}
                </Link>
              </div>
            </li>
          )}
        </ul>
      )}
      {!source.isPending && !source.isError && visible.length === 0 && (
        <p className="text-muted">{items.length === 0 ? t('listing.emptyCatalog') : t('listing.empty')}</p>
      )}
    </Container>
  )
}
