import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useParams } from 'react-router'
import { PageHeader } from '@/admin/components/PageHeader'
import { ErrorState } from '@/components/feedback/ErrorState'
import { ArrowLeftIcon, DownloadIcon } from '@/components/icons'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { buttonClasses } from '@/components/ui/buttonClasses'
import { Section } from '@/components/ui/Section'
import { Spinner } from '@/components/ui/Spinner'
import { ApiError } from '@/lib/api'
import { formatDate, formatDateTime } from '@/lib/dates'
import { formatEuros } from '@/lib/money'
import { adminBookingService } from '../bookingService'
import { BookingStatusBadge } from '../components/BookingStatusBadge'
import { CancelBookingDialog } from '../components/CancelBookingDialog'
import { GuestForm } from '../components/GuestForm'
import { useBooking, useResendConfirmation } from '../useAdminBookings'

/** Fiche d'une réservation : séjour, voyageur, prix, paiements et actions de l'hôte. */
export function BookingDetailPage() {
  const { t } = useTranslation()
  const { reference = '' } = useParams()
  const booking = useBooking(reference)
  const resend = useResendConfirmation(reference)
  const [cancelling, setCancelling] = useState(false)

  if (booking.isPending) return <Spinner label={t('common.loading')} />
  if (booking.isError) {
    if (booking.error instanceof ApiError && booking.error.status === 404) return <Alert tone="info">{t('admin.bookings.notFound')}</Alert>
    return <ErrorState onRetry={() => void booking.refetch()} />
  }

  const data = booking.data
  const confirmed = data.status === 'confirmed' || data.status === 'completed'

  return (
    <>
      <Link to="/admin/reservations" className="mb-3 inline-flex min-h-11 items-center gap-2 text-sm font-semibold no-underline">
        <ArrowLeftIcon width={18} height={18} />
        {t('admin.bookings.title')}
      </Link>
      <PageHeader
        title={`${data.guest_name} · ${data.reference}`}
        subtitle={`${data.apartment.name} · ${formatDate(data.arrival)} → ${formatDate(data.departure)}`}
        actions={
          <>
            {confirmed && (
              <>
                <a href={adminBookingService.pdfUrl(data.reference)} className={buttonClasses('secondary')}>
                  <DownloadIcon />
                  {t('admin.bookings.pdf')}
                </a>
                <Button variant="secondary" loading={resend.isPending} onClick={() => resend.mutate()}>
                  {t('admin.bookings.resend')}
                </Button>
              </>
            )}
            {data.refund_proposal && (
              <Button variant="danger" onClick={() => setCancelling(true)}>
                {t('admin.bookings.cancel')}
              </Button>
            )}
          </>
        }
      />
      {resend.isSuccess && <div className="mb-4"><Alert tone="success">{t('admin.bookings.resent', { email: data.guest_email })}</Alert></div>}

      <div className="grid gap-5 lg:grid-cols-2">
        <Section title={t('admin.bookings.stay')}>
          <dl className="grid grid-cols-[max-content_1fr] gap-x-4 gap-y-2 text-[15px]">
            <dt className="text-muted">{t('admin.bookings.status')}</dt>
            <dd><BookingStatusBadge status={data.status} /></dd>
            <dt className="text-muted">{t('admin.bookings.source')}</dt>
            <dd>{t(`admin.bookings.sources.${data.source}`)}</dd>
            <dt className="text-muted">{t('admin.bookings.guests')}</dt>
            <dd>{t('admin.bookings.adultsChildren', { adults: data.adults, children: data.children })}</dd>
            <dt className="text-muted">{t('admin.bookings.nights')}</dt>
            <dd>{data.nights}</dd>
            {data.confirmed_at && (
              <>
                <dt className="text-muted">{t('admin.bookings.confirmedAt')}</dt>
                <dd>{formatDateTime(data.confirmed_at)}</dd>
              </>
            )}
            {data.cancelled_at && (
              <>
                <dt className="text-muted">{t('admin.bookings.cancelledAt')}</dt>
                <dd>{formatDateTime(data.cancelled_at)}</dd>
              </>
            )}
          </dl>
          {data.guest_message && (
            <blockquote className="mt-4 rounded-xl bg-sand px-4 py-3 text-[15px] whitespace-pre-line">{data.guest_message}</blockquote>
          )}
        </Section>

        <Section title={t('admin.bookings.price')}>
          <ul className="space-y-1.5 text-[15px]">
            {data.price_lines.map((line, index) => (
              <li key={index} className="flex justify-between gap-3">
                <span>{line.label}</span>
                <span>{formatEuros(line.amount)}</span>
              </li>
            ))}
            <li className="flex justify-between gap-3 border-t border-border pt-2 font-semibold">
              <span>{t('admin.bookings.total')}</span>
              <span>{formatEuros(data.total_amount)}</span>
            </li>
          </ul>
          <h3 className="mt-5 mb-2 font-semibold">{t('admin.bookings.payments')}</h3>
          {data.payments.length === 0 ? (
            <p className="text-sm text-muted">{t('admin.bookings.noPayment')}</p>
          ) : (
            <ul className="space-y-1 text-sm">
              {data.payments.map((payment) => (
                <li key={payment.id} className="flex justify-between gap-3">
                  <span>
                    {t(`admin.bookings.paymentTypes.${payment.type}`)} · {t(`admin.bookings.paymentStatuses.${payment.status}`)}
                    {payment.created_at && ` · ${formatDateTime(payment.created_at)}`}
                  </span>
                  <span>{formatEuros(payment.amount)}</span>
                </li>
              ))}
            </ul>
          )}
          <p className="mt-4 text-sm text-muted">
            {t('admin.bookings.policy', { policy: data.cancellation_policy.policy })}{' '}
            {data.cancellation_policy.tiers.map((tier) => t('admin.bookings.tier', { days: tier.days_before, percent: tier.refund_percent })).join(' · ')}
          </p>
        </Section>

        <Section title={t('admin.bookings.guest')} className="lg:col-span-2">
          <GuestForm key={`${data.guest_email}-${data.guest_phone}-${data.housekeeping_notes}`} booking={data} />
        </Section>
      </div>

      {data.refund_proposal && <CancelBookingDialog booking={data} open={cancelling} onClose={() => setCancelling(false)} />}
    </>
  )
}
