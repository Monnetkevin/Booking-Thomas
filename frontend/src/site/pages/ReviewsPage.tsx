import { useTranslation } from 'react-i18next'
import { useSearchParams } from 'react-router'
import { ErrorState } from '@/components/feedback/ErrorState'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Container } from '@/components/ui/Container'
import { FilterChips } from '@/components/ui/FilterChips'
import { Skeleton } from '@/components/ui/Skeleton'
import { useApartments } from '@/site/catalog/useCatalog'
import { Seo } from '@/site/components/Seo'
import { ReviewCard } from '@/site/reviews/components/ReviewCard'
import { ReviewSummaryPanel } from '@/site/reviews/components/ReviewSummaryPanel'
import { usePublishedReviews } from '@/site/reviews/useReviews'

/** Tous les avis publiés, par logement, avec la moyenne et la répartition des notes. */
export function ReviewsPage() {
  const { t } = useTranslation()
  const [params, setParams] = useSearchParams()
  const apartment = params.get('logement')
  const apartments = useApartments()
  const reviews = usePublishedReviews(apartment)
  const first = reviews.data?.pages[0]

  return (
    <Container className="flex flex-col gap-8 py-10 sm:py-14">
      <Seo title={t('reviews.title')} description={t('reviews.metaDescription')} path="/avis" />
      <header className="flex flex-col gap-3">
        <h1 className="text-4xl font-semibold sm:text-[44px]">{t('reviews.title')}</h1>
        <p className="max-w-3xl text-[17px] leading-relaxed text-muted">{t('reviews.intro')}</p>
      </header>

      {(apartments.data?.length ?? 0) > 1 && (
        <FilterChips
          label={t('reviews.filter')}
          value={apartment ?? ''}
          onChange={(slug) => setParams(slug ? { logement: slug } : {}, { replace: true, preventScrollReset: true })}
          options={[{ value: '', label: t('reviews.allApartments') }, ...(apartments.data ?? []).map((item) => ({ value: item.slug, label: item.name }))]}
        />
      )}

      {reviews.isError ? (
        <ErrorState onRetry={() => void reviews.refetch()} />
      ) : !first ? (
        <div className="flex flex-col gap-5" aria-busy="true">
          <Skeleton className="h-44 rounded-[var(--radius-card)]" />
          <div className="grid gap-5 md:grid-cols-2">
            <Skeleton className="h-56 rounded-[var(--radius-card)]" />
            <Skeleton className="h-56 rounded-[var(--radius-card)]" />
          </div>
        </div>
      ) : first.summary.count === 0 ? (
        <Alert tone="info">{t('reviews.empty')}</Alert>
      ) : (
        <>
          <ReviewSummaryPanel summary={first.summary} />
          <div className="grid gap-5 md:grid-cols-2">
            {(reviews.data?.pages ?? []).flatMap((page) => page.data).map((review) => (
              <ReviewCard key={review.id} review={review} showApartment={apartment === null} />
            ))}
          </div>
          {reviews.hasNextPage && (
            <Button variant="secondary" size="lg" className="self-center" loading={reviews.isFetchingNextPage} onClick={() => void reviews.fetchNextPage()}>
              {t('reviews.more')}
            </Button>
          )}
        </>
      )}
    </Container>
  )
}
