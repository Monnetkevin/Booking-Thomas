import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { RatingStars } from '@/components/ui/RatingStars'
import { useLocale } from '@/i18n/useLocale'
import { usePublishedReviews } from '@/site/reviews/useReviews'

/** Haut de page : note moyenne et nombre d'avis, vers la page des avis ; rien sans avis publié. */
export function HeroRating() {
  const { t } = useTranslation()
  const { locale, to } = useLocale()
  // Même requête que la section « Ils ont séjourné chez nous » : un seul appel.
  const summary = usePublishedReviews(null).data?.pages[0]?.summary
  if (!summary || summary.average === null) return null
  const average = summary.average.toLocaleString(locale === 'fr' ? 'fr-FR' : 'en-GB', { minimumFractionDigits: 1 })

  return (
    <Link
      to={to('/avis')}
      className="flex items-center gap-2 self-start rounded-full border border-white/45 bg-white/15 px-3.5 py-2 text-[13px] font-bold text-white no-underline backdrop-blur-sm hover:bg-white/25 hover:text-white focus-visible:outline-white"
    >
      <RatingStars value={summary.average} label={t('reviews.average', { average })} size={14} tone="light" />
      {t('reviews.heroSummary', { average, count: summary.count })}
    </Link>
  )
}
