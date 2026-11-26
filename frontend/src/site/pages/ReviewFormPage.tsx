import { useState, type FormEvent } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useParams } from 'react-router'
import { ErrorState } from '@/components/feedback/ErrorState'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Checkbox, TextArea, TextField } from '@/components/ui/Field'
import { Container } from '@/components/ui/Container'
import { Skeleton } from '@/components/ui/Skeleton'
import { useLocale } from '@/i18n/useLocale'
import { ApiError } from '@/lib/api'
import { formatDate } from '@/lib/dates'
import { Seo } from '@/site/components/Seo'
import { StarRatingInput } from '@/site/reviews/components/StarRatingInput'
import { REVIEW_COMMENT_MAX } from '@/site/reviews/types'
import { useReviewInvitation, useSubmitReview } from '@/site/reviews/useReviews'

/** Formulaire d'avis, depuis le lien personnel reçu par e-mail après le séjour. */
export function ReviewFormPage() {
  const { t } = useTranslation()
  const { locale, to } = useLocale()
  const { token = '' } = useParams()
  const invitation = useReviewInvitation(token)
  const submit = useSubmitReview(token)
  const [rating, setRating] = useState(0)
  const [comment, setComment] = useState('')
  const [displayName, setDisplayName] = useState<string | null>(null)
  const [consent, setConsent] = useState(false)

  const dateLocale = locale === 'fr' ? 'fr-FR' : 'en-GB'
  const linkGone = (error: unknown) => error instanceof ApiError && [404, 410].includes(error.status)
  const fieldErrors = submit.error instanceof ApiError ? submit.error.fieldErrors : {}
  const error = (field: string) => fieldErrors[field]?.[0]

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    submit.mutate({ rating, comment, display_name: displayName ?? invitation.data?.first_name ?? '', consent })
  }

  const header = (
    <div className="flex flex-col gap-2">
      <p className="text-[13px] font-bold tracking-[0.08em] text-accent uppercase">{t('review.eyebrow')}</p>
      <h1 className="text-[34px] leading-[1.1] sm:text-[40px]">{t('review.title')}</h1>
    </div>
  )

  return (
    <Container className="flex max-w-2xl flex-col gap-6 py-10 sm:py-14">
      <Seo title={t('review.title')} description={t('review.title')} path="/avis" noIndex />
      {linkGone(invitation.error) || linkGone(submit.error) ? (
        <>
          <h1 className="text-[34px] leading-[1.1] sm:text-[40px]">{t('review.invalidTitle')}</h1>
          <p className="text-[17px] text-muted">{t('review.invalidText')}</p>
          <Link to={to('/contact')} className="font-semibold">
            {t('review.contact')}
          </Link>
        </>
      ) : invitation.isError ? (
        <ErrorState onRetry={() => void invitation.refetch()} />
      ) : !invitation.data ? (
        <div className="flex flex-col gap-4" aria-busy="true">
          <Skeleton className="h-12 w-2/3" />
          <Skeleton className="h-24 rounded-[var(--radius-card)]" />
          <Skeleton className="h-96 rounded-[var(--radius-card)]" />
        </div>
      ) : submit.isSuccess ? (
        <>
          {header}
          <Alert tone="success">{submit.data.message}</Alert>
          <Link to={to('/')} className="font-semibold">
            {t('review.backHome')}
          </Link>
        </>
      ) : (
        <>
          {header}
          <p className="text-[17px] leading-relaxed text-muted">{t('review.intro')}</p>
          <div className="flex flex-col gap-0.5 rounded-[var(--radius-card)] border border-border bg-white p-4">
            <strong className="text-[17px]">{invitation.data.apartment}</strong>
            <span className="text-sm text-muted">
              {t('review.stay', {
                from: formatDate(invitation.data.arrival, dateLocale),
                to: formatDate(invitation.data.departure, dateLocale),
                count: invitation.data.nights,
              })}
            </span>
          </div>
          <form onSubmit={handleSubmit} noValidate className="flex flex-col gap-5 rounded-[var(--radius-card)] border border-border bg-white p-5 sm:p-7">
            {submit.error && !(submit.error instanceof ApiError && submit.error.status === 422) && <Alert tone="danger">{t('common.error')}</Alert>}
            <StarRatingInput value={rating} onChange={setRating} error={error('rating')} />
            <TextArea
              label={t('review.comment')}
              placeholder={t('review.commentPlaceholder')}
              hint={t('review.commentHint', { count: REVIEW_COMMENT_MAX })}
              required
              rows={6}
              maxLength={REVIEW_COMMENT_MAX}
              value={comment}
              error={error('comment')}
              onChange={(event) => setComment(event.target.value)}
            />
            <TextField
              label={t('review.displayName')}
              hint={t('review.displayNameHint')}
              required
              maxLength={50}
              autoComplete="given-name"
              value={displayName ?? invitation.data.first_name}
              error={error('display_name')}
              onChange={(event) => setDisplayName(event.target.value)}
            />
            <div>
              <Checkbox label={t('review.consent')} checked={consent} onChange={(event) => setConsent(event.target.checked)} />
              {error('consent') && <p className="text-sm text-danger">{t('review.consentRequired')}</p>}
            </div>
            <Button type="submit" size="lg" loading={submit.isPending}>
              {t('review.submit')}
            </Button>
            <p className="text-[13px] leading-relaxed text-muted">
              {t('review.moderation', { date: formatDate(invitation.data.expires_on, dateLocale) })}
            </p>
          </form>
        </>
      )}
    </Container>
  )
}
