import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useParams } from 'react-router'
import { initials } from '@/admin/components/initials'
import { PageHeader } from '@/admin/components/PageHeader'
import { ErrorState } from '@/components/feedback/ErrorState'
import { ArrowLeftIcon, CopyIcon, DownloadIcon, MailIcon } from '@/components/icons'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { buttonClasses } from '@/components/ui/buttonClasses'
import { Section } from '@/components/ui/Section'
import { Spinner } from '@/components/ui/Spinner'
import { ApiError } from '@/lib/api'
import { formatDateTime, formatLongDate } from '@/lib/dates'
import { formatEuros } from '@/lib/money'
import { BookingHistoryList } from '../components/BookingHistoryList'
import { BookingStatusBadge } from '../components/BookingStatusBadge'
import { CancelBookingDialog } from '../components/CancelBookingDialog'
import { GuestForm } from '../components/GuestForm'
import { useBooking, useDownloadBookingPdf, useResendConfirmation, useUpdateBooking } from '../useAdminBookings'

/** Adresse mailto sûre : l'e-mail est encodé (pas de cc, bcc ou body ajoutés), l'arobase reste lisible. */
function mailto(email: string): string {
  return `mailto:${encodeURIComponent(email).replaceAll('%40', '@')}`
}

/**
 * Fiche d'une réservation : séjour, voyageur (écrire, copier l'e-mail, corriger), historique,
 * paiement et actions (confirmation, PDF, impression, annulation).
 */
export function BookingDetailPage() {
  const { t } = useTranslation()
  const { reference = '' } = useParams()
  const booking = useBooking(reference)
  const resend = useResendConfirmation(reference)
  const pdf = useDownloadBookingPdf(reference)
  const update = useUpdateBooking(reference)
  const [cancelling, setCancelling] = useState(false)
  const [editing, setEditing] = useState(false)
  const [copied, setCopied] = useState(false)

  if (booking.isPending) return <Spinner label={t('common.loading')} />
  if (booking.isError) {
    if (booking.error instanceof ApiError && booking.error.status === 404) return <Alert tone="info">{t('admin.bookings.notFound')}</Alert>
    return <ErrorState onRetry={() => void booking.refetch()} />
  }

  const data = booking.data
  const confirmed = data.status === 'confirmed' || data.status === 'completed'
  const copyEmail = () => {
    if (!data.guest_email) return
    void navigator.clipboard?.writeText(data.guest_email).then(() => setCopied(true))
  }

  return (
    <>
      <Link to="/admin/reservations" className="mb-3 inline-flex min-h-11 items-center gap-2 text-sm font-semibold no-underline print:hidden">
        <ArrowLeftIcon width={18} height={18} />
        {t('admin.bookings.title')}
      </Link>
      <PageHeader
        title={t('admin.bookings.detailTitle', { reference: data.reference })}
        subtitle={data.created_at ? t('admin.bookings.createdOn', { date: formatDateTime(data.created_at) }) : undefined}
        actions={
          <Button variant="secondary" onClick={() => window.print()} className="print:hidden">
            {t('admin.bookings.print')}
          </Button>
        }
      />
      {resend.isSuccess && (
        <div className="mb-4">
          <Alert tone="success">{t('admin.bookings.resent', { email: data.guest_email ?? '' })}</Alert>
        </div>
      )}
      {[pdf, resend].map((mutation, index) =>
        mutation.isError ? (
          <div key={index} className="mb-4">
            <Alert tone="danger">{(mutation.error instanceof ApiError && mutation.error.serverMessage) || t('common.error')}</Alert>
          </div>
        ) : null,
      )}

      <div className="grid gap-5 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)] [&>*]:min-w-0">
        <div className="space-y-5">
          <Section title={t('admin.bookings.stay')} actions={<BookingStatusBadge status={data.status} />}>
            <dl className="grid grid-cols-[max-content_1fr] gap-x-6 gap-y-2.5 text-[15px]">
              <dt className="text-muted">{t('admin.bookings.columns.apartment')}</dt>
              <dd className="font-semibold">{data.apartment.name}</dd>
              <dt className="text-muted">{t('admin.bookings.arrival')}</dt>
              <dd className="font-semibold first-letter:uppercase">{formatLongDate(data.arrival)}</dd>
              <dt className="text-muted">{t('admin.bookings.departure')}</dt>
              <dd className="font-semibold first-letter:uppercase">{formatLongDate(data.departure)}</dd>
              <dt className="text-muted">{t('admin.bookings.duration')}</dt>
              <dd className="font-semibold">{t('admin.bookings.nightsCount', { count: data.nights })}</dd>
              <dt className="text-muted">{t('admin.bookings.guests')}</dt>
              <dd className="font-semibold">{t('admin.bookings.adultsChildren', { adults: data.adults, children: data.children })}</dd>
              <dt className="text-muted">{t('admin.bookings.source')}</dt>
              <dd className="font-semibold">{t(`admin.bookings.sources.${data.source}`)}</dd>
            </dl>
            {data.guest_message && (
              <blockquote className="mt-4 rounded-xl bg-sand px-4 py-3 text-[15px] whitespace-pre-line">{data.guest_message}</blockquote>
            )}
          </Section>

          <Section
            title={t('admin.bookings.guest')}
            actions={
              <Button variant="ghost" aria-expanded={editing} onClick={() => setEditing((value) => !value)} className="print:hidden">
                {editing ? t('admin.common.cancel') : t('admin.bookings.editGuest')}
              </Button>
            }
          >
            <div className="flex flex-wrap items-center gap-3">
              <span aria-hidden="true" className="flex size-11 shrink-0 items-center justify-center rounded-full bg-accent-soft font-bold text-accent-strong">
                {initials(data.guest_name)}
              </span>
              <div className="min-w-0 flex-1">
                <p className="font-semibold">{data.guest_name}</p>
                <p className="text-sm break-all text-muted">{[data.guest_email, data.guest_phone].filter(Boolean).join(' · ') || t('admin.bookings.noContact')}</p>
              </div>
              {data.guest_email && (
                <div className="flex flex-wrap gap-2 print:hidden">
                  <a href={mailto(data.guest_email)} className={buttonClasses('secondary')}>
                    <MailIcon width={18} height={18} />
                    {t('admin.bookings.write')}
                  </a>
                  <Button variant="secondary" onClick={copyEmail}>
                    <CopyIcon width={18} height={18} />
                    {copied ? t('admin.bookings.copied') : t('admin.bookings.copyEmail')}
                  </Button>
                </div>
              )}
            </div>
            {data.housekeeping_notes && !editing && (
              <p className="mt-3 text-sm text-muted">
                {t('admin.bookings.housekeepingNotes')} : {data.housekeeping_notes}
              </p>
            )}
            {editing && (
              <div className="mt-5 border-t border-line pt-5">
                <GuestForm key={`${data.guest_email}-${data.guest_phone}-${data.housekeeping_notes}`} booking={data} update={update} />
              </div>
            )}
          </Section>

          <Section title={t('admin.bookings.history')}>
            <BookingHistoryList events={data.history} />
          </Section>
        </div>

        <div className="space-y-5">
          <Section title={t('admin.bookings.payment')}>
            <ul className="space-y-1.5 text-[15px]">
              {data.price_lines.map((line, index) => (
                <li key={index} className="flex justify-between gap-3">
                  <span>{line.label}</span>
                  <span className="tabular-nums">{formatEuros(line.amount)}</span>
                </li>
              ))}
              <li className="flex justify-between gap-3 border-t border-border pt-2 text-lg font-bold">
                <span>{t('admin.bookings.total')}</span>
                <span className="tabular-nums">{formatEuros(data.total_amount)}</span>
              </li>
            </ul>
            <h3 className="mt-5 mb-2 font-sans text-sm font-bold tracking-normal">{t('admin.bookings.payments')}</h3>
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
                    <span className="tabular-nums">{formatEuros(payment.amount)}</span>
                  </li>
                ))}
              </ul>
            )}
            <p className="mt-4 text-sm text-muted">
              {t('admin.bookings.policy', { policy: data.cancellation_policy.policy })}{' '}
              {data.cancellation_policy.tiers.map((tier) => t('admin.bookings.tier', { days: tier.days_before, percent: tier.refund_percent })).join(' · ')}
            </p>
          </Section>

          {(confirmed || data.refund_proposal) && (
            <Section title={t('admin.bookings.actions')} className="print:hidden">
              <div className="flex flex-col gap-2.5 [&>*]:w-full [&>*]:justify-center">
                {confirmed && data.guest_email && (
                  <Button variant="secondary" loading={resend.isPending} onClick={() => resend.mutate()}>
                    {t('admin.bookings.resend')}
                  </Button>
                )}
                {confirmed && (
                  <Button variant="secondary" loading={pdf.isPending} onClick={() => pdf.mutate()}>
                    <DownloadIcon />
                    {t('admin.bookings.pdf')}
                  </Button>
                )}
                {data.refund_proposal && (
                  <Button variant="danger" onClick={() => setCancelling(true)}>
                    {t('admin.bookings.cancel')}
                  </Button>
                )}
              </div>
            </Section>
          )}
        </div>
      </div>

      {data.refund_proposal && <CancelBookingDialog booking={data} open={cancelling} onClose={() => setCancelling(false)} />}
    </>
  )
}
