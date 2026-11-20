import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link, useNavigate } from 'react-router'
import { useApartmentList } from '@/admin/apartments/useApartments'
import { PageHeader } from '@/admin/components/PageHeader'
import { ErrorState } from '@/components/feedback/ErrorState'
import { DownloadIcon, PlusIcon } from '@/components/icons'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { buttonClasses } from '@/components/ui/buttonClasses'
import { Card } from '@/components/ui/Card'
import { DateField, SelectField, TextField } from '@/components/ui/Field'
import { Spinner } from '@/components/ui/Spinner'
import { cn } from '@/lib/cn'
import { formatEuros } from '@/lib/money'
import { BookingStatusBadge } from '../components/BookingStatusBadge'
import { PaymentStateBadge } from '../components/PaymentStateBadge'
import { stayLabel } from '../stayLabel'
import { BOOKING_STAGES, type BookingFilters, type BookingSource, type BookingStage } from '../types'
import { useBookingList, useDownloadBookingsCsv } from '../useAdminBookings'

/**
 * Réservations : onglets avec compteurs (à venir, en cours, terminées, annulées), filtres
 * (logement, source, période, recherche), export CSV de la liste filtrée, tableau sur
 * ordinateur et cartes sur téléphone.
 */
export function BookingsPage() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const [filters, setFilters] = useState<BookingFilters>({})
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const [showFilters, setShowFilters] = useState(false)
  const apartments = useApartmentList()
  const bookings = useBookingList(filters, page)
  const exportCsv = useDownloadBookingsCsv()
  const counts = bookings.data?.meta.counts
  const activeFilters = [filters.apartment_id, filters.source, filters.from, filters.to].filter(Boolean).length

  const setFilter = (patch: BookingFilters) => {
    setFilters((current) => ({ ...current, ...patch }))
    setPage(1)
  }

  const tabs: { value: BookingStage | undefined; label: string; count?: number }[] = [
    { value: undefined, label: t('admin.bookings.stages.all'), count: counts?.all },
    ...BOOKING_STAGES.map((stage) => ({ value: stage, label: t(`admin.bookings.stages.${stage}`), count: counts?.[stage] })),
  ]

  return (
    <>
      <PageHeader
        title={t('admin.bookings.title')}
        subtitle={counts ? t('admin.bookings.subtitleCount', { count: counts.all }) : t('admin.bookings.subtitle')}
        actions={
          <Link to="/admin/reservations/nouvelle" className={buttonClasses('primary')}>
            <PlusIcon />
            {t('admin.bookings.create')}
          </Link>
        }
      />

      <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div role="group" aria-label={t('admin.bookings.stagesLabel')} className="flex flex-wrap gap-1.5">
          {tabs.map((tab) => {
            const active = tab.value === filters.stage
            return (
              <button
                key={tab.value ?? 'all'}
                type="button"
                aria-pressed={active}
                onClick={() => setFilter({ stage: tab.value })}
                className={cn(
                  'flex min-h-10 items-center gap-2 rounded-xl px-3.5 text-sm font-bold',
                  active ? 'bg-ink text-white' : 'bg-white text-ink ring-1 ring-line hover:bg-cream',
                )}
              >
                {tab.label}
                {tab.count !== undefined && (
                  <span className={cn('rounded-full px-1.5 text-xs tabular-nums', active ? 'bg-white/20' : 'bg-cream text-muted')}>{tab.count}</span>
                )}
              </button>
            )
          })}
        </div>
        <div className="flex flex-wrap gap-2">
          <Button variant="secondary" aria-expanded={showFilters} aria-controls="booking-filters" onClick={() => setShowFilters((value) => !value)}>
            {t('admin.bookings.filters')}
            {activeFilters > 0 && <span className="rounded-full bg-accent px-1.5 text-xs text-white">{activeFilters}</span>}
          </Button>
          <Button variant="secondary" loading={exportCsv.isPending} onClick={() => exportCsv.mutate(filters)}>
            <DownloadIcon />
            {t('admin.bookings.export')}
          </Button>
        </div>
      </div>

      <form
        id="booking-filters"
        hidden={!showFilters}
        className="mb-4 grid gap-3 rounded-[var(--radius-card)] border border-border bg-white p-4 sm:grid-cols-2 lg:grid-cols-5"
        onSubmit={(event) => {
          event.preventDefault()
          setFilter({ search: search.trim() || undefined })
        }}
      >
        <TextField
          label={t('admin.bookings.search')}
          value={search}
          onChange={(event) => setSearch(event.target.value)}
          onBlur={() => setFilter({ search: search.trim() || undefined })}
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
        <DateField label={t('admin.bookings.from')} value={filters.from ?? ''} onChange={(value) => setFilter({ from: value || undefined })} />
        <DateField label={t('admin.bookings.to')} value={filters.to ?? ''} min={filters.from} onChange={(value) => setFilter({ to: value || undefined })} />
      </form>

      {exportCsv.isError && (
        <div className="mb-4">
          <Alert tone="danger">{t('common.error')}</Alert>
        </div>
      )}

      {bookings.isPending ? (
        <Spinner label={t('common.loading')} />
      ) : bookings.isError ? (
        <ErrorState onRetry={() => void bookings.refetch()} />
      ) : bookings.data.data.length === 0 ? (
        <Alert tone="info">{t('admin.bookings.empty')}</Alert>
      ) : (
        <Card className="p-0 sm:p-0">
          {/* Ordinateur : tableau ; chaque ligne mène à la fiche (lien sur la référence, ligne cliquable). */}
          <div className="relative hidden overflow-x-auto lg:block">
            <table className="w-full text-left text-sm">
              <thead>
                <tr className="border-b border-border text-xs tracking-wide text-muted uppercase">
                  {(['reference', 'guest', 'apartment', 'stay', 'nights', 'total', 'source', 'status', 'payment'] as const).map((column) => (
                    <th key={column} scope="col" className={cn('px-4 py-3 font-semibold', (column === 'nights' || column === 'total') && 'text-right')}>
                      {t(`admin.bookings.columns.${column}`)}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {bookings.data.data.map((booking) => (
                  <tr
                    key={booking.reference}
                    onClick={() => void navigate(`/admin/reservations/${booking.reference}`)}
                    className="cursor-pointer border-b border-line last:border-0 hover:bg-sand"
                  >
                    <td className="px-4 py-3 font-semibold whitespace-nowrap">
                      <Link to={`/admin/reservations/${booking.reference}`} onClick={(event) => event.stopPropagation()} className="text-ink">
                        {booking.reference}
                      </Link>
                    </td>
                    <td className="px-4 py-3">
                      <span className="block font-semibold">{booking.guest_name}</span>
                      <span className="text-xs text-muted">{t('admin.bookings.guestsCount', { count: booking.guests })}</span>
                    </td>
                    <td className="px-4 py-3">{booking.apartment.name}</td>
                    <td className="px-4 py-3 whitespace-nowrap">{stayLabel(booking.arrival, booking.departure)}</td>
                    <td className="px-4 py-3 text-right tabular-nums">{booking.nights}</td>
                    <td className="px-4 py-3 text-right font-semibold whitespace-nowrap tabular-nums">{formatEuros(booking.total_amount)}</td>
                    <td className="px-4 py-3">{t(`admin.bookings.sources.${booking.source}`)}</td>
                    <td className="px-4 py-3"><BookingStatusBadge status={booking.status} /></td>
                    <td className="px-4 py-3">{booking.payment_state && <PaymentStateBadge state={booking.payment_state} />}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {/* Téléphone et tablette : cartes. */}
          <ul className="divide-y divide-border lg:hidden">
            {bookings.data.data.map((booking) => (
              <li key={booking.reference}>
                <Link to={`/admin/reservations/${booking.reference}`} className="flex flex-col gap-1.5 px-4 py-3.5 text-ink no-underline hover:bg-sand">
                  <span className="flex items-start justify-between gap-3">
                    <span className="font-semibold">{booking.guest_name}</span>
                    <span className="font-semibold tabular-nums">{formatEuros(booking.total_amount)}</span>
                  </span>
                  <span className="text-sm text-muted">
                    {booking.apartment.name} · {stayLabel(booking.arrival, booking.departure)} · {t('admin.bookings.nightsGuests', { nights: booking.nights, guests: booking.guests })}
                  </span>
                  <span className="flex flex-wrap items-center gap-2">
                    <BookingStatusBadge status={booking.status} />
                    {booking.payment_state && <PaymentStateBadge state={booking.payment_state} />}
                    <span className="text-xs text-muted">{booking.reference}</span>
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
