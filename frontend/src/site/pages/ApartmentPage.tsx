import { lazy, Suspense } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useParams, useSearchParams } from 'react-router'
import { ErrorState } from '@/components/feedback/ErrorState'
import { Alert } from '@/components/ui/Alert'
import { Container } from '@/components/ui/Container'
import { Skeleton } from '@/components/ui/Skeleton'
import { useLocale } from '@/i18n/useLocale'
import { ApiError } from '@/lib/api'
import { formatDate } from '@/lib/dates'
import { formatEuros } from '@/lib/money'
import { stayFromParams, stayToParams } from '@/site/catalog/searchParams'
import type { ApartmentDetail } from '@/site/catalog/types'
import { useApartmentDetail } from '@/site/catalog/useCatalog'
import { BookingPanel } from '@/site/components/BookingPanel'
import { PhotoGallery } from '@/site/components/PhotoGallery'
import { Seo } from '@/site/components/Seo'

// Leaflet n'est chargé que sur les fiches, et seulement si le logement a une position.
const LocationMap = lazy(() => import('@/site/components/LocationMap'))

/** Données structurées schema.org (VacationRental) : jamais l'adresse exacte. */
function structuredData(apartment: ApartmentDetail, url: string) {
  return {
    '@context': 'https://schema.org',
    '@type': 'VacationRental',
    name: apartment.name,
    description: apartment.description ?? apartment.tagline ?? undefined,
    url,
    image: apartment.photos.map((photo) => photo.sources.large.webp).filter(Boolean),
    address: {
      '@type': 'PostalAddress',
      streetAddress: apartment.location.street,
      postalCode: apartment.location.postal_code,
      addressLocality: apartment.location.city,
      addressCountry: 'FR',
    },
    ...(apartment.location.latitude !== null && {
      geo: { '@type': 'GeoCoordinates', latitude: apartment.location.latitude, longitude: apartment.location.longitude },
    }),
    checkinTime: apartment.check_in_time,
    checkoutTime: apartment.check_out_time,
    containsPlace: {
      '@type': 'Accommodation',
      additionalType: 'EntirePlace',
      occupancy: { '@type': 'QuantitativeValue', maxValue: apartment.capacity },
      numberOfBedrooms: apartment.bedrooms,
      numberOfBathroomsTotal: apartment.bathrooms,
      ...(apartment.surface_m2 && { floorSize: { '@type': 'QuantitativeValue', value: apartment.surface_m2, unitCode: 'MTK' } }),
      amenityFeature: apartment.amenities.map((amenity) => ({ '@type': 'LocationFeatureSpecification', name: amenity.label, value: true })),
    },
  }
}

function DetailSkeleton() {
  return (
    <Container className="flex flex-col gap-6 py-8" aria-busy="true">
      <Skeleton className="h-5 w-48" />
      <Skeleton className="h-10 w-2/3" />
      <Skeleton className="aspect-[16/9] w-full sm:h-[432px] sm:aspect-auto" />
      <div className="grid gap-10 lg:grid-cols-[1fr_400px]">
        <div className="flex flex-col gap-3">
          <Skeleton className="h-6 w-40" />
          <Skeleton className="h-24 w-full" />
        </div>
        <Skeleton className="h-[520px]" />
      </div>
    </Container>
  )
}

function InfoRow({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex justify-between gap-4 border-b border-line py-3.5 text-base">
      <dt>{label}</dt>
      <dd className="text-right font-semibold">{value}</dd>
    </div>
  )
}

export function ApartmentPage() {
  const { t } = useTranslation()
  const { locale, to } = useLocale()
  const numberLocale = locale === 'fr' ? 'fr-FR' : 'en-GB'
  const slug = useParams().slug ?? ''
  const [params, setParams] = useSearchParams()
  const query = useApartmentDetail(slug)
  const stay = stayFromParams(params)

  if (query.isPending) return <DetailSkeleton />
  if (query.isError) {
    if (query.error instanceof ApiError && query.error.status === 404) {
      return (
        <Container className="flex flex-col items-start gap-4 py-16">
          <Seo title={t('notFound.title')} description={t('detail.notFound')} path={`/logements/${slug}`} noIndex />
          <Alert tone="warning">{t('detail.notFound')}</Alert>
          <Link to={to('/logements')}>{t('detail.backToList')}</Link>
        </Container>
      )
    }
    return (
      <Container className="py-16">
        <ErrorState onRetry={() => void query.refetch()} />
      </Container>
    )
  }

  const apartment = query.data
  const path = `/logements/${apartment.slug}`
  const meta = [
    apartment.city,
    t('listing.bedrooms', { count: apartment.bedrooms }),
    t('search.guests', { count: apartment.capacity }),
    apartment.surface_m2 ? t('listing.surface', { value: apartment.surface_m2 }) : null,
  ].filter(Boolean)
  const tiers = apartment.cancellation.tiers

  return (
    <Container className="flex flex-col gap-6 py-8 sm:py-10">
      <Seo
        title={apartment.name}
        description={apartment.tagline ?? apartment.description?.slice(0, 155) ?? t('listing.metaDescription')}
        path={path}
        image={apartment.photos[0]?.sources.large.webp}
        jsonLd={structuredData(apartment, window.location.origin + to(path))}
      />

      <nav aria-label={t('detail.breadcrumb')} className="text-sm text-muted">
        <ol className="flex flex-wrap gap-1.5">
          <li>
            <Link to={to('/logements')}>{t('nav.apartments')}</Link>
            <span aria-hidden="true"> /</span>
          </li>
          <li aria-current="page" className="font-semibold text-ink">{apartment.name}</li>
        </ol>
      </nav>

      <header className="flex flex-col gap-2">
        <h1 className="text-[32px] leading-tight font-semibold sm:text-[40px]">{apartment.name}</h1>
        <p className="text-base text-muted">{meta.join(' · ')}</p>
      </header>

      <PhotoGallery photos={apartment.photos} name={apartment.name} />

      <div className="grid items-start gap-10 lg:grid-cols-[minmax(0,1fr)_400px]">
        <div className="order-2 flex min-w-0 flex-col gap-10 lg:order-1">
          {(apartment.tagline || apartment.description) && (
            <section>
              <h2 className="mb-3 text-[22px] font-semibold">{t('detail.about')}</h2>
              {apartment.tagline && <p className="mb-2 text-[17px] font-semibold text-accent-strong">{apartment.tagline}</p>}
              {apartment.description?.split(/\n{2,}/).map((paragraph) => (
                <p key={paragraph.slice(0, 32)} className="mb-3 text-base leading-relaxed whitespace-pre-line text-[#3a2e24]">
                  {paragraph}
                </p>
              ))}
            </section>
          )}

          {apartment.amenities.length > 0 && (
            <section>
              <h2 className="mb-4 text-[22px] font-semibold">{t('detail.amenities')}</h2>
              <ul className="grid gap-x-6 gap-y-3.5 sm:grid-cols-2">
                {apartment.amenities.map((amenity) => (
                  <li key={amenity.key} className="flex items-center gap-3 text-base">
                    <svg aria-hidden="true" width="20" height="20" viewBox="0 0 20 20" className="shrink-0 text-accent">
                      <path d="M4 10.5l4 4 8-9" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                    </svg>
                    {amenity.label}
                  </li>
                ))}
              </ul>
            </section>
          )}

          <section>
            <h2 className="mb-2 text-[22px] font-semibold">{t('detail.rules')}</h2>
            <dl>
              <InfoRow label={t('detail.checkIn')} value={t('detail.checkInValue', { time: apartment.check_in_time })} />
              <InfoRow label={t('detail.checkOut')} value={t('detail.checkOutValue', { time: apartment.check_out_time })} />
              <InfoRow label={t('detail.minStay')} value={t('detail.nights', { count: apartment.min_stay })} />
              <InfoRow label={t('detail.maxStay')} value={t('detail.nights', { count: apartment.max_stay })} />
            </dl>
            {tiers.length > 0 && (
              <div className="mt-4">
                <h3 className="text-base font-semibold">
                  {t('detail.cancellation', { policy: t(`detail.policies.${apartment.cancellation.policy}`) })}
                </h3>
                <ul className="mt-1 list-disc pl-5 text-[15px] text-muted">
                  {tiers.map((tier) =>
                    tier.days_before > 0 ? (
                      <li key={tier.days_before}>{t('detail.cancellationTier', { percent: tier.refund_percent, days: tier.days_before })}</li>
                    ) : (
                      <li key="last">{t('detail.cancellationTierLast', { percent: tier.refund_percent })}</li>
                    ),
                  )}
                </ul>
              </div>
            )}
          </section>

          {apartment.rates.length > 0 && (
            <section>
              <h2 className="mb-2 text-[22px] font-semibold">{t('detail.rates')}</h2>
              <ul className="divide-y divide-line">
                {apartment.rates.map((rate) => (
                  <li key={rate.starts_on} className="flex flex-wrap justify-between gap-x-4 gap-y-1 py-3.5">
                    <span>
                      {rate.label && <strong className="block">{rate.label}</strong>}
                      <span className="text-muted">
                        {t('detail.rateDates', { from: formatDate(rate.starts_on, numberLocale), to: formatDate(rate.ends_on, numberLocale) })}
                      </span>
                    </span>
                    <span className="text-right">
                      <strong className="block">{t('detail.ratePrice', { price: formatEuros(rate.nightly_price, numberLocale) })}</strong>
                      <span className="text-sm text-muted">
                        {[
                          t('detail.rateMinStay', { count: rate.min_stay }),
                          rate.weekend_supplement ? t('detail.rateWeekend', { price: formatEuros(rate.weekend_supplement, numberLocale) }) : null,
                        ]
                          .filter(Boolean)
                          .join(' · ')}
                      </span>
                    </span>
                  </li>
                ))}
              </ul>
              {apartment.cleaning_fee > 0 && (
                <p className="mt-2 text-sm text-muted">{t('detail.cleaningFee', { price: formatEuros(apartment.cleaning_fee, numberLocale) })}</p>
              )}
            </section>
          )}

          <section>
            <h2 className="mb-2 text-[22px] font-semibold">{t('detail.location')}</h2>
            <p className="mb-4 text-[15px] text-muted">
              {apartment.location.street}, {apartment.location.postal_code} {apartment.location.city}. {t('detail.locationNote')}
            </p>
            {apartment.location.latitude !== null && apartment.location.longitude !== null ? (
              <Suspense fallback={<Skeleton className="h-[260px]" />}>
                <LocationMap
                  latitude={apartment.location.latitude}
                  longitude={apartment.location.longitude}
                  label={t('detail.mapLabel', { name: apartment.name })}
                />
              </Suspense>
            ) : (
              <p className="text-sm text-muted">{t('detail.mapUnavailable')}</p>
            )}
            {apartment.registration_number && (
              <p className="mt-3 text-sm text-muted">{t('detail.registration', { number: apartment.registration_number })}</p>
            )}
          </section>
        </div>

        <aside className="order-1 lg:sticky lg:top-20 lg:order-2">
          <BookingPanel
            apartment={apartment}
            stay={stay}
            onChange={(next) => setParams(stayToParams(next), { replace: true, preventScrollReset: true })}
          />
        </aside>
      </div>
    </Container>
  )
}
