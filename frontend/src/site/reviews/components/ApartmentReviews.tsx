import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { RatingStars } from '@/components/ui/RatingStars'
import { useLocale } from '@/i18n/useLocale'
import { usePublishedReviews } from '../useReviews'
import { ReviewCard } from './ReviewCard'

/** Avis de la fiche logement : moyenne, deux derniers avis, lien vers tous ; rien sans avis. */
export function ApartmentReviews({ slug }: { slug: string }) {
  const { t } = useTranslation()
  const { locale, to } = useLocale()
  const reviews = usePublishedReviews(slug)
  const first = reviews.data?.pages[0]
  if (!first || first.summary.average === null) return null
  const average = first.summary.average.toLocaleString(locale === 'fr' ? 'fr-FR' : 'en-GB', { minimumFractionDigits: 1 })

  return (
    <section>
      <div className="mb-4 flex flex-wrap items-center gap-x-3 gap-y-1">
        <h2 className="text-[22px] font-semibold">{t('reviews.sectionTitle')}</h2>
        <span className="flex items-center gap-2 text-[15px] text-muted">
          <RatingStars value={first.summary.average} label={t('reviews.average', { average })} />
          <strong className="text-ink">{average}</strong>
          {t('reviews.count', { count: first.summary.count })}
        </span>
      </div>
      <div className="grid gap-4">
        {first.data.slice(0, 2).map((review) => (
          <ReviewCard key={review.id} review={review} showApartment={false} />
        ))}
      </div>
      {first.summary.count > 2 && (
        <Link to={`${to('/avis')}?logement=${encodeURIComponent(slug)}`} className="mt-4 inline-block font-semibold">
          {t('reviews.seeAll', { count: first.summary.count })}
        </Link>
      )}
    </section>
  )
}
