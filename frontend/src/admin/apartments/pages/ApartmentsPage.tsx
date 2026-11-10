import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { PageHeader } from '@/admin/components/PageHeader'
import { ErrorState } from '@/components/feedback/ErrorState'
import { PlusIcon } from '@/components/icons'
import { buttonClasses } from '@/components/ui/buttonClasses'
import { FilterChips } from '@/components/ui/FilterChips'
import { Spinner } from '@/components/ui/Spinner'
import { formatEuros } from '@/lib/money'
import { StatusBadge } from '../components/StatusBadge'
import type { ApartmentListItem, ApartmentStatus } from '../types'
import { useApartmentList } from '../useApartments'

type Filter = 'all' | ApartmentStatus

function Thumbnail({ apartment }: { apartment: ApartmentListItem }) {
  return apartment.cover_url ? (
    <img src={apartment.cover_url} alt="" width={56} height={42} className="h-[42px] w-14 shrink-0 rounded-[10px] object-cover" />
  ) : (
    <span aria-hidden="true" className="h-[42px] w-14 shrink-0 rounded-[10px] bg-cream" />
  )
}

export function ApartmentsPage() {
  const { t } = useTranslation()
  const [filter, setFilter] = useState<Filter>('all')
  const list = useApartmentList(filter === 'all' ? undefined : filter)

  const price = (apartment: ApartmentListItem) =>
    apartment.base_price === null
      ? t('admin.apartments.noRate')
      : t('admin.apartments.perNight', { price: formatEuros(apartment.base_price) })

  return (
    <>
      <PageHeader
        title={t('admin.apartments.title')}
        subtitle={list.data ? t('admin.apartments.count', { count: list.data.length }) : undefined}
        actions={
          <Link to="/admin/appartements/nouveau" className={buttonClasses('primary')}>
            <PlusIcon />
            {t('admin.apartments.add')}
          </Link>
        }
      />

      <div className="mb-5">
        <FilterChips<Filter>
          label={t('admin.apartments.filters.label')}
          value={filter}
          onChange={setFilter}
          options={[
            { value: 'all', label: t('admin.apartments.filters.all') },
            { value: 'published', label: t('admin.apartments.filters.published') },
            { value: 'draft', label: t('admin.apartments.filters.draft') },
          ]}
        />
      </div>

      {list.isPending ? (
        <Spinner label={t('common.loading')} />
      ) : list.isError ? (
        <ErrorState onRetry={() => void list.refetch()} />
      ) : list.data.length === 0 ? (
        <p className="rounded-[var(--radius-card)] border border-border bg-white p-6 text-muted">
          {filter === 'all' ? t('admin.apartments.empty') : t('admin.apartments.emptyFiltered')}
        </p>
      ) : (
        <>
        {/* Téléphone : une carte par logement */}
        <ul className="flex flex-col gap-3 sm:hidden">
          {list.data.map((apartment) => (
            <li key={apartment.id}>
              <Link
                to={`/admin/appartements/${apartment.id}`}
                className="flex items-center gap-3 rounded-[var(--radius-card)] border border-border bg-white p-3 text-ink no-underline hover:text-ink"
                aria-label={t('admin.apartments.editLabel', { name: apartment.name })}
              >
                <Thumbnail apartment={apartment} />
                <span className="flex min-w-0 flex-1 flex-col gap-1">
                  <strong className="truncate text-[15px]">{apartment.name}</strong>
                  <span className="text-sm text-muted">
                    {t('admin.apartments.guests', { count: apartment.capacity })} · {price(apartment)}
                  </span>
                </span>
                <StatusBadge status={apartment.status} />
              </Link>
            </li>
          ))}
        </ul>
        <div className="hidden overflow-x-auto rounded-[var(--radius-card)] border border-border bg-white sm:block">
          <table className="w-full min-w-[640px] text-left text-sm">
            <thead>
              <tr className="border-b border-[#c9b8a6] text-xs font-bold tracking-[0.04em] text-muted uppercase">
                <th scope="col" className="px-5 py-3">{t('admin.apartments.columns.apartment')}</th>
                <th scope="col" className="px-3 py-3">{t('admin.apartments.columns.status')}</th>
                <th scope="col" className="px-3 py-3">{t('admin.apartments.columns.capacity')}</th>
                <th scope="col" className="px-3 py-3">{t('admin.apartments.columns.basePrice')}</th>
                <th scope="col" className="px-5 py-3"><span className="sr-only">{t('admin.apartments.columns.actions')}</span></th>
              </tr>
            </thead>
            <tbody>
              {list.data.map((apartment) => (
                <tr key={apartment.id} className="border-b border-line last:border-0">
                  <td className="px-5 py-3">
                    <span className="flex items-center gap-3">
                      <Thumbnail apartment={apartment} />
                      <strong className="text-[15px]">{apartment.name}</strong>
                    </span>
                  </td>
                  <td className="px-3 py-3"><StatusBadge status={apartment.status} /></td>
                  <td className="px-3 py-3">{t('admin.apartments.guests', { count: apartment.capacity })}</td>
                  <td className="px-3 py-3">{price(apartment)}</td>
                  <td className="px-5 py-3 text-right">
                    <Link
                      to={`/admin/appartements/${apartment.id}`}
                      className="inline-flex min-h-11 items-center font-semibold no-underline"
                      aria-label={t('admin.apartments.editLabel', { name: apartment.name })}
                    >
                      {t('admin.common.edit')}
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        </>
      )}
    </>
  )
}
