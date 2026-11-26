import { useTranslation } from 'react-i18next'
import { RatingStars } from '@/components/ui/RatingStars'
import { useLocale } from '@/i18n/useLocale'
import { formatMonth } from '@/lib/dates'
import type { PublishedReview } from '../types'

/** Un avis publié : note, mois du séjour, texte, prénom, « séjour vérifié », réponse de l'hôte. */
export function ReviewCard({ review, showApartment = true }: { review: PublishedReview; showApartment?: boolean }) {
  const { t } = useTranslation()
  const { locale } = useLocale()

  return (
    <article className="flex flex-col gap-3.5 rounded-[var(--radius-card)] border border-border bg-white p-6">
      <div className="flex items-center justify-between gap-3">
        <RatingStars value={review.rating} label={t('reviews.rating', { count: review.rating })} />
        <span className="text-[13px] text-muted">{formatMonth(review.stay_month, locale === 'fr' ? 'fr-FR' : 'en-GB')}</span>
      </div>
      <p className="text-base leading-relaxed whitespace-pre-line text-[#3a2e24]">« {review.comment} »</p>
      <p className="flex flex-col gap-0.5 text-sm text-muted">
        <strong className="text-ink">{review.display_name}</strong>
        <span>{showApartment ? t('reviews.verifiedIn', { apartment: review.apartment.name }) : t('reviews.verified')}</span>
      </p>
      {review.host_reply && (
        <div className="rounded-xl bg-cream px-4 py-3.5 text-sm leading-relaxed text-[#3a2e24]">
          <strong>{t('reviews.hostReply')}</strong>
          <br />
          {review.host_reply}
        </div>
      )}
    </article>
  )
}
