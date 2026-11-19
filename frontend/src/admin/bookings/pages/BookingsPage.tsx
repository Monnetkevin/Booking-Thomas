import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { useApartmentList } from '@/admin/apartments/useApartments'
import { PageHeader } from '@/admin/components/PageHeader'
import { ErrorState } from '@/components/feedback/ErrorState'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { SelectField, TextField } from '@/components/ui/Field'
import { Spinner } from '@/components/ui/Spinner'
import { formatDate } from '@/lib/dates'
import { formatEuros } from '@/lib/money'
import { BookingStatusBadge } from '../components/BookingStatusBadge'
import { BOOKING_STATUSES, type BookingFilters, type BookingSource, type BookingStatus } from '../types'
import { useBookingList } from '../useAdminBookings'

/** Toutes les réservations du site, filtrées par statut, logement, source ou recherche. */
export function BookingsPage() {
  const { t } = useTranslation()
  const [filters, setFilters] = useState<BookingFilters>({})
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const apartments = useApartmentList()
  const bookings = useBookingList(filters, page)

  const setFilter = (patch: BookingFilters) => {
    setFilters((current) => ({ ...current, ...patch }))
    setPage(1)
  }

  return (
    <>
      <PageHeader
        title={t('admin.bookings.title')}
        subtitle={t('admin.bookings.subtitle')}
      />
      <form
        className="mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
        onSubmit={(event) => {
          event.preventDefault()
          setFilter({ search: search.trim() || undefined })
        }}
      >
        <TextField label={t('admin.bookings.search')} value={search} onChange={(event) => setSearch(event.target.value)} onBlur={() => setFilter({ search: search.trim() || undefined })} />
        <SelectField
          label={t('admin.bookings.status')}
          value={filters.status ?? ''}
          onChange={(value) => setFilter({ status: (value || undefined) as BookingStatus | undefined })}
          options={[{ value: '', label: t('admin.bookings.allStatuses') }, ...BOOKING_STATUSES.map((status) => ({ value: status, label: t(`admin.bookings.statuses.${status}`) }))]}
        />
        <SelectField
          label={t('admin.bookings.apartment')}
          value={filters.apartment_id ? String(filters.apartment_id) : ''}
          onChange={(value) => setFilter({ apartment_id: value ? Number(value) : undefined })}
          options={[{ value: '', label: t('admin.bookings.allApartments') }, ...(apartments.data ?? []).map((apartment) => ({ value: String(apartment.id), label: apartment.name }))]}
        />
        <SelectField
          label={t('admin.bookings.source')}
          value={filters.source ?? ''}
          onChange={(value) => setFilter({ source: (value || undefined) as BookingSource | undefined })}
          options={[
            { value: '', label: t('admin.bookings.allSources') },
            { value: 'direct', label: t('admin.bookings.sources.direct') },
            { value: 'manual', label: t('admin.bookings.sources.manual') },
          ]}
        />
      </form>

      {bookings.isPending ? (
        <Spinner label={t('common.loading')} />
      ) : bookings.isError ? (
        <ErrorState onRetry={() => void bookings.refetch()} />
      ) : bookings.data.data.length === 0 ? (
        <Alert tone="info">{t('admin.bookings.empty')}</Alert>
      ) : (
        <Card className="p-0">
          <ul className="divide-y divide-border">
            {bookings.data.data.map((booking) => (
              <li key={booking.reference}>
                <Link
                  to={`/admin/reservations/${booking.reference}`}
                  className="grid gap-1 px-5 py-4 text-ink no-underline hover:bg-sand sm:grid-cols-[1fr_auto] sm:items-center"
                >
                  <span>
                    <span className="flex flex-wrap items-center gap-2 font-semibold">
                      {booking.guest_name}
                      <BookingStatusBadge status={booking.status} />
                      {booking.source === 'manual' && <span className="text-sm font-normal text-muted">{t('admin.bookings.sources.manual')}</span>}
                    </span>
                    <span className="block text-sm text-muted">
                      {booking.apartment.name} · {formatDate(booking.arrival)} → {formatDate(booking.departure)} ·{' '}
                      {t('admin.bookings.nightsGuests', { nights: booking.nights, guests: booking.guests })}
                    </span>
                  </span>
                  <span className="text-sm sm:text-right">
                    <span className="block font-semibold">{formatEuros(booking.total_amount)}</span>
                    <span className="text-muted">{booking.reference}</span>
                  </span>
                </Link>
              </li>
            ))}
          </ul>
          {bookings.data.meta.last_page > 1 && (
            <nav aria-label={t('admin.bookings.pages')} className="flex items-center justify-between gap-3 border-t border-border px-5 py-3">
              <Button variant="secondary" disabled={page <= 1} onClick={() => setPage((value) => value - 1)}>
                {t('common.previous')}
              </Button>
              <span className="text-sm text-muted">{t('admin.notifications.page', { page, total: bookings.data.meta.last_page })}</span>
              <Button variant="secondary" disabled={page >= bookings.data.meta.last_page} onClick={() => setPage((value) => value + 1)}>
                {t('common.next')}
              </Button>
            </nav>
          )}
        </Card>
      )}
    </>
  )
}
