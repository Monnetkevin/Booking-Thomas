import { useState } from 'react'
import { useTranslation } from 'react-i18next'
import { Link } from 'react-router'
import { useCurrentUser } from '@/admin/auth/useAuth'
import { PageHeader } from '@/admin/components/PageHeader'
import { ErrorState } from '@/components/feedback/ErrorState'
import { PlusIcon } from '@/components/icons'
import { buttonClasses } from '@/components/ui/buttonClasses'
import { cn } from '@/lib/cn'
import { Spinner } from '@/components/ui/Spinner'
import { ApartmentCard } from '../components/ApartmentCard'
import type { ApartmentStatus } from '../types'
import { useApartmentOverview } from '../useApartments'

type Filter = 'all' | ApartmentStatus
const FILTERS: Filter[] = ['all', 'published', 'draft']

/** Logements en cartes : filtres par statut avec leur nombre, occupation du mois, ajout. */
export function ApartmentsPage() {
  const { t } = useTranslation()
  const [filter, setFilter] = useState<Filter>('all')
  const { data: user } = useCurrentUser()
  const list = useApartmentOverview(filter === 'all' ? undefined : filter)
  const counts = list.data?.meta.counts

  return (
    <>
      <PageHeader
        title={t('admin.apartments.title')}
        subtitle={counts ? t('admin.apartments.summary', { count: counts.all, published: counts.published }) : undefined}
        actions={
          <Link to="/admin/appartements/nouveau" className={buttonClasses('primary')}>
            <PlusIcon />
            {t('admin.apartments.add')}
          </Link>
        }
      />

      <div role="group" aria-label={t('admin.apartments.filters.label')} className="mb-5 flex flex-wrap gap-1.5">
        {FILTERS.map((value) => (
          <button
            key={value}
            type="button"
            aria-pressed={filter === value}
            onClick={() => setFilter(value)}
            className={cn(
              'flex min-h-10 items-center gap-2 rounded-xl px-3.5 text-sm font-bold',
              filter === value ? 'bg-ink text-white' : 'bg-white text-ink ring-1 ring-line hover:bg-cream',
            )}
          >
            {t(`admin.apartments.filters.${value}`)}
            {counts && <span className={cn('rounded-full px-1.5 text-xs', filter === value ? 'bg-white/20' : 'bg-cream text-muted')}>{counts[value]}</span>}
          </button>
        ))}
      </div>

      {list.isPending ? (
        <Spinner label={t('common.loading')} />
      ) : list.isError ? (
        <ErrorState onRetry={() => void list.refetch()} />
      ) : (
        <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
          {list.data.data.map((apartment) => (
            <li key={apartment.id} className="flex">
              <div className="flex-1">
                <ApartmentCard apartment={apartment} occupancy={list.data.meta.occupancy[apartment.id]} showPrice={user?.role === 'admin'} />
              </div>
            </li>
          ))}
          <li className="flex">
            <Link
              to="/admin/appartements/nouveau"
              className="flex min-h-56 flex-1 flex-col items-center justify-center gap-2 rounded-[var(--radius-card)] border-2 border-dashed border-border text-sm font-bold text-muted no-underline hover:border-accent hover:text-accent-strong"
            >
              <PlusIcon />
              {t('admin.apartments.add')}
            </Link>
          </li>
        </ul>
      )}
      {list.data && list.data.data.length === 0 && (
        <p className="mt-4 text-muted">{filter === 'all' ? t('admin.apartments.empty') : t('admin.apartments.emptyFiltered')}</p>
      )}
    </>
  )
}
