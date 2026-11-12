import { useTranslation } from 'react-i18next'
import { Link, useParams, useSearchParams } from 'react-router'
import { QuoteBreakdown } from '@/components/QuoteBreakdown'
import { ErrorState } from '@/components/feedback/ErrorState'
import { Container } from '@/components/ui/Container'
import { Skeleton } from '@/components/ui/Skeleton'
import { useLocale } from '@/i18n/useLocale'
import { ApiError } from '@/lib/api'
import { BookingStatusBanner } from '@/site/booking/BookingStatusBanner'
import { BookingStaySummary } from '@/site/booking/BookingStaySummary'
import { useBookingTracking } from '@/site/booking/useBookingTracking'
import { CancellationTiers } from '@/site/components/CancellationTiers'
import { Seo } from '@/site/components/Seo'

/** Suivi d'une réservation par le voyageur, depuis le lien signé reçu par e-mail. */
export function BookingPage() {
  const { t } = useTranslation()
  const { locale, to } = useLocale()
  const { reference = '' } = useParams()
  const [params] = useSearchParams()
  const signature = params.get('signature') ?? ''
  const query = useBookingTracking(reference, signature)
  const invalidLink = signature === '' || (query.error instanceof ApiError && [403, 404].includes(query.error.status))

  const contact = (
    <p className="text-[15px] text-muted">
      {t('tracking.question')}{' '}
      <Link to={to('/contact')} className="font-semibold">
        {t('tracking.contact')}
      </Link>
    </p>
  )

  return (
    <Container className="flex max-w-3xl flex-col gap-6 py-10 sm:py-14">
      <Seo title={t('tracking.title')} description={t('tracking.title')} path={`/reservation/${reference}`} noIndex />
      <p className="text-[13px] font-bold tracking-[0.08em] text-muted uppercase">{t('tracking.reference', { reference })}</p>

      {invalidLink ? (
        <>
          <h1 className="text-[34px] leading-[1.08] sm:text-[44px]">{t('tracking.invalidTitle')}</h1>
          <p className="text-[17px] text-muted">{t('tracking.invalidText')}</p>
          {contact}
        </>
      ) : query.isError ? (
        <ErrorState onRetry={() => void query.refetch()} />
      ) : !query.data ? (
        <div className="flex flex-col gap-4" aria-busy="true">
          <Skeleton className="h-12 w-2/3" />
          <Skeleton className="h-14" />
          <Skeleton className="h-80 rounded-[var(--radius-card)]" />
        </div>
      ) : (
        <>
          <BookingStatusBanner booking={query.data} />
          <BookingStaySummary booking={query.data} />
          <section aria-labelledby="prix-titre" className="rounded-[var(--radius-card)] border border-line bg-white p-5 sm:p-7">
            <h2 id="prix-titre" className="mb-4 text-xl">
              {t('tracking.priceTitle')}
            </h2>
            <QuoteBreakdown quote={query.data.price} locale={locale === 'fr' ? 'fr-FR' : 'en-GB'} />
          </section>
          <CancellationTiers terms={query.data.cancellation} />
          {query.data.status === 'expired' && (
            <Link to={to(`/logements/${query.data.apartment.slug}`)} className="self-start font-semibold">
              {t('tracking.rebook')}
            </Link>
          )}
          {contact}
        </>
      )}
    </Container>
  )
}
