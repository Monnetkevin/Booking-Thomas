import { useTranslation } from 'react-i18next'
import { UnderlineLink } from '@/components/ui/UnderlineLink'
import { useLocale } from '@/i18n/useLocale'
import { PageSection } from '@/site/components/PageSection'
import { usePublishedReviews } from '../useReviews'
import { ReviewCard } from './ReviewCard'

/** Accueil : les trois derniers avis publiés et le lien vers tous ; rien tant qu'il n'y en a pas. */
export function LatestReviews() {
  const { t } = useTranslation()
  const { to } = useLocale()
  const reviews = usePublishedReviews(null)
  const latest = reviews.data?.pages[0]?.data.slice(0, 3) ?? []
  if (latest.length === 0) return null

  return (
    <PageSection title={t('reviews.homeTitle')} action={<UnderlineLink to={to('/avis')}>{t('reviews.readAll')}</UnderlineLink>}>
      <p className="-mt-4 max-w-2xl text-[17px] leading-relaxed text-muted">{t('reviews.homeIntro')}</p>
      <div className="grid gap-5 md:grid-cols-3">
        {latest.map((review) => (
          <ReviewCard key={review.id} review={review} />
        ))}
      </div>
    </PageSection>
  )
}
