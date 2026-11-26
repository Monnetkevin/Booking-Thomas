import type { ReactNode } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { Badge, type BadgeTone } from '@/components/ui/Badge'
import { Card } from '@/components/ui/Card'
import { RatingStars } from '@/components/ui/RatingStars'
import { formatDate } from '@/lib/dates'
import type { AdminReview, ReviewStatus } from '../types'

const tones: Record<ReviewStatus, BadgeTone> = { pending: 'warning', published: 'success', rejected: 'danger', invited: 'neutral' }

/** Un avis (ou une invitation sans réponse) de la file de modération, avec ses actions. */
export function AdminReviewCard({ review, actions }: { review: AdminReview; actions: ReactNode }) {
  const { t } = useTranslation()
  const stay = t('admin.reviews.stay', { from: formatDate(review.booking.arrival), to: formatDate(review.booking.departure) })

  return (
    <Card className="flex flex-col gap-3 p-5">
      <div className="flex items-center justify-between gap-3">
        {review.rating !== null ? (
          <RatingStars value={review.rating} label={t('admin.reviews.rating', { count: review.rating })} />
        ) : (
          <span className="text-sm text-muted">{t('admin.reviews.noAnswer')}</span>
        )}
        <Badge tone={tones[review.status]}>{t(`admin.reviews.statuses.${review.status}`)}</Badge>
      </div>
      {review.comment && <p className="text-[15px] leading-relaxed whitespace-pre-line">« {review.comment} »</p>}
      <p className="text-[13px] text-muted">
        <strong className="text-ink">{review.display_name ?? review.booking.guest_name}</strong> · {review.apartment.name} · {stay} ·{' '}
        <Link to={`/admin/reservations/${review.booking.reference}`} className="whitespace-nowrap">
          {review.booking.reference}
        </Link>
        <br />
        {review.submitted_at
          ? t('admin.reviews.receivedOn', { date: formatDate(review.submitted_at.slice(0, 10)) })
          : t('admin.reviews.invitedOn', { date: formatDate(review.invited_at.slice(0, 10)), expires: formatDate(review.expires_at.slice(0, 10)) })}
      </p>
      {review.rejection_reason && <p className="text-[13px] text-muted">{t('admin.reviews.reason', { reason: review.rejection_reason })}</p>}
      {review.host_reply && (
        <div className="rounded-xl bg-cream px-4 py-3 text-sm leading-relaxed">
          <strong>{t('admin.reviews.yourReply')}</strong>
          <br />
          {review.host_reply}
        </div>
      )}
      <div className="flex flex-wrap gap-2.5">{actions}</div>
    </Card>
  )
}
